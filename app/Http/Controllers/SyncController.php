<?php

namespace App\Http\Controllers;

use App\Models\Breed;
use App\Models\BreedingRecord;
use App\Models\BudgetEntry;
use App\Models\Caretaker;
use App\Models\Goat;
use App\Models\HealthRecord;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\OwnershipUnit;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    /**
     * Batch-process queued offline operations.
     *
     * Accepts: { operations: [ { id, entity, action, data } ] }
     * Returns: { results: [ { id, status, entity_id?, error? } ] }
     *
     * Each operation is processed in a transaction so one failure
     * doesn't roll back the whole batch.  Individual failures are
     * reported back so the client can keep them in the queue.
     */
    public function push(Request $request)
    {
        $validated = $request->validate([
            'operations'            => ['required', 'array'],
            'operations.*.id'       => ['required', 'string'],
            'operations.*.entity'   => ['required', 'string'],
            'operations.*.action'   => ['required', 'in:create,update,delete'],
            'operations.*.data'     => ['nullable', 'array'],
            'operations.*.record_id'=> ['nullable', 'integer'], // for update/delete
        ]);

        $results = [];

        foreach ($validated['operations'] as $op) {
            $result = ['id' => $op['id'], 'status' => 'failed'];

            try {
                $entityId = $this->processOperation($op);
                $result['status']     = 'success';
                $result['entity_id']  = $entityId;
            } catch (\Illuminate\Validation\ValidationException $e) {
                $result['status'] = 'error';
                $result['error']  = $e->errors();
            } catch (\Exception $e) {
                $result['status'] = 'error';
                $result['error']  = $e->getMessage();
            }

            $results[] = $result;
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Return all entity data as JSON for offline caching.
     * Includes reference data (breeds, caretakers, investors, goats)
     * needed to render create forms offline.
     */
    public function pull(Request $request)
    {
        return response()->json([
            'synced_at'  => now()->toIso8601String(),
            'data'       => [
                'goats'          => Goat::with('breed:id,name', 'caretaker:id,name')
                                        ->select(['id','tag_id','name','breed_id','caretaker_id','gender','dob','weight_kg','purchase_date','purchase_weight','vendor','status','purchase_price','sale_price','sale_date','sale_weight','estimated_value','photo','notes'])
                                        ->get(),
                'breeds'         => Breed::select(['id','name','description'])->get(),
                'caretakers'     => Caretaker::select(['id','name','phone','every_nth_kid','start_date','active','notes'])->get(),
                'investors'      => Investor::select(['id','name','role','expense_split_pct','email','phone','address','notes'])->get(),
                'transactions'   => Transaction::select(['id','type','category','amount','date','goat_id','investor_id','payment_method','is_recurring','budget_amount','notes'])
                                        ->latest('date')->limit(200)->get(),
                'health_records' => HealthRecord::select(['id','goat_id','type','date','cost','notes'])->latest('date')->limit(200)->get(),
                'breeding_records' => BreedingRecord::select(['id','sire_id','dam_id','breeding_date','kidding_date','expected_kidding_date','pregnancy_status','litter_size','kidding_difficulty','notes'])
                                        ->latest('breeding_date')->limit(200)->get(),
                'investments'    => Investment::select(['id','investor_id','amount','date','notes'])->latest('date')->limit(200)->get(),
                'budget_entries' => BudgetEntry::select(['id','type','category','year','jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'])->get(),
                'ownership_units'=> OwnershipUnit::select(['id','goat_id','investor_id','share_pct'])->get(),
            ],
        ]);
    }

    /**
     * Process a single operation and return the model ID.
     *
     * @throws \Exception|\Illuminate\Validation\ValidationException
     */
    private function processOperation(array $op): int
    {
        $entity  = $op['entity'];
        $action  = $op['action'];
        $data    = $op['data'] ?? [];
        $recordId = $op['record_id'] ?? null;

        // Handle delete separately — it's the same for all entities
        if ($action === 'delete') {
            if (!$recordId) {
                throw new \Exception('Delete operation requires a record_id.');
            }
            return DB::transaction(function () use ($entity, $recordId): int {
                $model = $this->resolveModel($entity, $recordId);
                $model->delete();
                return $recordId;
            });
        }

        return DB::transaction(function () use ($entity, $action, $data, $recordId): int {
            switch ($entity) {
                case 'goat':           return $this->processGoat($action, $data, $recordId);
                case 'transaction':    return $this->processTransaction($action, $data, $recordId);
                case 'health_record':  return $this->processHealthRecord($action, $data, $recordId);
                case 'breeding_record':return $this->processBreedingRecord($action, $data, $recordId);
                case 'investment':     return $this->processInvestment($action, $data, $recordId);
                case 'caretaker':      return $this->processCaretaker($action, $data, $recordId);
                case 'breed':          return $this->processBreed($action, $data, $recordId);
                case 'investor':       return $this->processInvestor($action, $data, $recordId);
                case 'budget_entry':   return $this->processBudgetEntry($action, $data, $recordId);
                case 'ownership_unit': return $this->processOwnershipUnit($action, $data, $recordId);
                default:
                    throw new \Exception("Unknown entity: {$entity}");
            }
        });
    }

    // ─── GOATS ────────────────────────────────────────────────────────

    private function processGoat(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'tag_id'           => ['required', 'string', 'max:100', 'unique:goats,tag_id' . ($action === 'update' && $recordId ? ",{$recordId}" : '')],
            'name'             => ['nullable', 'string', 'max:100'],
            'breed_id'         => ['required', 'exists:breeds,id'],
            'caretaker_id'     => ['nullable', 'exists:caretakers,id'],
            'gender'           => ['required', 'in:male,female'],
            'dob'              => ['nullable', 'date', 'before_or_equal:today'],
            'weight_kg'        => ['nullable', 'numeric', 'min:0'],
            'purchase_date'    => ['nullable', 'date'],
            'purchase_weight'  => ['nullable', 'numeric', 'min:0'],
            'vendor'           => ['nullable', 'string', 'max:255'],
            'status'           => ['required', 'in:kid,grower,breeder,sold,deceased'],
            'purchase_price'   => ['nullable', 'numeric', 'min:0'],
            'sale_price'       => ['nullable', 'numeric', 'min:0'],
            'sale_date'        => ['nullable', 'date'],
            'sale_weight'      => ['nullable', 'numeric', 'min:0'],
            'estimated_value'  => ['nullable', 'numeric', 'min:0'],
            'photo_base64'     => ['nullable', 'string'], // from offline blob
            'notes'            => ['nullable', 'string'],
        ])->validate();

        // Handle base64 photo
        if (!empty($validated['photo_base64'])) {
            $validated['photo'] = $this->saveBase64Image($validated['photo_base64'], 'goats');
        }
        unset($validated['photo_base64']);

        if ($action === 'create') {
            return Goat::create($validated)->id;
        }

        $goat = Goat::findOrFail($recordId);
        if (isset($validated['photo']) && $goat->photo) {
            Storage::disk('public')->delete($goat->photo);
        }
        $goat->update($validated);
        return $goat->id;
    }

    // ─── TRANSACTIONS ──────────────────────────────────────────────────

    private function processTransaction(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'type'           => ['required', 'in:income,expense'],
            'category'       => ['required', 'string', 'max:100'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'date'           => ['required', 'date'],
            'goat_id'        => ['nullable', 'exists:goats,id'],
            'investor_id'    => ['nullable', 'exists:investors,id'],
            'payment_method' => ['nullable', 'in:cash,bank_transfer,check,other'],
            'is_recurring'   => ['nullable', 'boolean'],
            'budget_amount'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string'],
        ])->validate();

        $validated['is_recurring'] = filter_var($validated['is_recurring'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($action === 'create') {
            return Transaction::create($validated)->id;
        }

        $transaction = Transaction::findOrFail($recordId);
        $transaction->update($validated);
        return $transaction->id;
    }

    // ─── HEALTH RECORDS ────────────────────────────────────────────────

    private function processHealthRecord(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'goat_id' => ['required', 'exists:goats,id'],
            'type'    => ['required', 'in:vaccination,treatment,checkup,other'],
            'date'    => ['required', 'date', 'before_or_equal:today'],
            'cost'    => ['nullable', 'numeric', 'min:0'],
            'notes'   => ['nullable', 'string'],
        ])->validate();

        $validated['cost'] = $validated['cost'] ?? 0;

        if ($action === 'create') {
            return HealthRecord::create($validated)->id;
        }

        // Health records don't have update in the web UI, but handle it gracefully
        $record = HealthRecord::findOrFail($recordId);
        $record->update($validated);
        return $record->id;
    }

    // ─── BREEDING RECORDS ──────────────────────────────────────────────

    private function processBreedingRecord(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'sire_id'       => ['required', 'exists:goats,id'],
            'dam_id'        => ['required', 'exists:goats,id', 'different:sire_id'],
            'breeding_date' => ['required', 'date', 'before_or_equal:today'],
            'kidding_date'  => ['nullable', 'date', 'after_or_equal:breeding_date'],
            'litter_size'   => ['nullable', 'integer', 'min:0', 'max:20'],
            'notes'         => ['nullable', 'string'],
        ])->validate();

        // Auto-calculate expected kidding date (same logic as BreedingRecordController::store)
        $kiddingDate = $validated['kidding_date'] ?? null;
        $validated['expected_kidding_date'] = $kiddingDate
            ? null
            : \Carbon\Carbon::parse($validated['breeding_date'])->addDays(BreedingRecord::GESTATION_DAYS);

        $validated['pregnancy_status'] = $kiddingDate ? 'kidded' : 'bred';
        $validated['litter_size']      = $validated['litter_size'] ?? 0;

        if ($action === 'create') {
            return BreedingRecord::create($validated)->id;
        }

        $record = BreedingRecord::findOrFail($recordId);
        // Preserve 'pregnant' status on update if it was already confirmed
        if (!$kiddingDate && $record->pregnancy_status === 'pregnant') {
            $validated['pregnancy_status'] = 'pregnant';
        }
        $record->update($validated);
        return $record->id;
    }

    // ─── INVESTMENTS ───────────────────────────────────────────────────

    private function processInvestment(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'investor_id' => ['required', 'exists:investors,id'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'date'        => ['required', 'date', 'before_or_equal:today'],
            'notes'       => ['nullable', 'string'],
        ])->validate();

        if ($action === 'create') {
            return Investment::create($validated)->id;
        }

        $investment = Investment::findOrFail($recordId);
        $investment->update($validated);
        return $investment->id;
    }

    // ─── CARETAKERS ────────────────────────────────────────────────────

    private function processCaretaker(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'every_nth_kid' => ['required', 'integer', 'min:1', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'active'        => ['boolean'],
            'notes'         => ['nullable', 'string'],
        ])->validate();

        $validated['active'] = filter_var($validated['active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($action === 'create') {
            return Caretaker::create($validated)->id;
        }

        $caretaker = Caretaker::findOrFail($recordId);
        $caretaker->update($validated);
        return $caretaker->id;
    }

    // ─── BREEDS ────────────────────────────────────────────────────────

    private function processBreed(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'name'        => ['required', 'string', 'max:255', 'unique:breeds,name' . ($action === 'update' && $recordId ? ",{$recordId}" : '')],
            'description' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        if ($action === 'create') {
            return Breed::create($validated)->id;
        }

        $breed = Breed::findOrFail($recordId);
        $breed->update($validated);
        return $breed->id;
    }

    // ─── INVESTORS ─────────────────────────────────────────────────────

    private function processInvestor(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'name'              => ['required', 'string', 'max:255'],
            'role'              => ['required', 'in:investor,manager'],
            'expense_split_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'email'             => ['nullable', 'email', 'max:255'],
            'phone'             => ['nullable', 'string', 'max:50'],
            'address'           => ['nullable', 'string', 'max:500'],
            'notes'             => ['nullable', 'string'],
        ])->validate();

        if ($action === 'create') {
            return Investor::create($validated)->id;
        }

        $investor = Investor::findOrFail($recordId);
        $investor->update($validated);
        return $investor->id;
    }

    // ─── BUDGET ENTRIES ────────────────────────────────────────────────

    private function processBudgetEntry(string $action, array $data, ?int $recordId): int
    {
        $monthRules = array_fill_keys(BudgetEntry::MONTHS, ['nullable', 'numeric', 'min:0']);

        $rules = array_merge([
            'type'     => $action === 'create' ? ['required', 'in:income,expense'] : ['nullable', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'year'     => $action === 'create' ? ['required', 'integer', 'min:2020', 'max:2050'] : ['nullable', 'integer', 'min:2020', 'max:2050'],
        ], $monthRules);

        $validated = validator($data, $rules)->validate();

        if ($action === 'create') {
            foreach (BudgetEntry::MONTHS as $m) {
                $validated[$m] = $validated[$m] ?? 0;
            }
            return BudgetEntry::create($validated)->id;
        }

        $entry = BudgetEntry::findOrFail($recordId);
        $entry->update($validated);
        return $entry->id;
    }

    // ─── OWNERSHIP UNITS ───────────────────────────────────────────────

    private function processOwnershipUnit(string $action, array $data, ?int $recordId): int
    {
        $validated = validator($data, [
            'goat_id'    => ['nullable', 'exists:goats,id'],
            'investor_id'=> ['required', 'exists:investors,id'],
            'share_pct'  => ['required', 'numeric', 'min:0', 'max:100'],
        ])->validate();

        if ($action === 'create') {
            return OwnershipUnit::create($validated)->id;
        }

        $unit = OwnershipUnit::findOrFail($recordId);
        $unit->update($validated);
        return $unit->id;
    }

    // ─── HELPER: Resolve model instance for delete ─────────────────────

    private function resolveModel(string $entity, int $recordId)
    {
        $classMap = [
            'goat'            => Goat::class,
            'transaction'     => Transaction::class,
            'health_record'   => HealthRecord::class,
            'breeding_record' => BreedingRecord::class,
            'investment'      => Investment::class,
            'caretaker'       => Caretaker::class,
            'breed'           => Breed::class,
            'investor'        => Investor::class,
            'budget_entry'    => BudgetEntry::class,
            'ownership_unit'  => OwnershipUnit::class,
        ];

        if (!isset($classMap[$entity])) {
            throw new \Exception("Unknown entity: {$entity}");
        }

        return $classMap[$entity]::findOrFail($recordId);
    }

    // ─── HELPER: Save base64 image to storage ──────────────────────────

    private function saveBase64Image(string $base64, string $dir): string
    {
        // Extract MIME type from data URI prefix if present
        $mime = 'image/jpeg'; // default
        if (Str::startsWith($base64, 'data:')) {
            if (preg_match('/^data:(image\/\w+);base64,/', $base64, $matches)) {
                $mime = $matches[1];
            }
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        }

        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            throw new \Exception('Invalid image type. Allowed: JPEG, PNG, WebP.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new \Exception('Invalid base64 image data.');
        }

        // Size check (max 2MB, matching GoatController's max:2048KB)
        if (strlen($binary) > 2048 * 1024) {
            throw new \Exception('Image too large. Maximum size is 2MB.');
        }

        $ext = match ($mime) {
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };

        $filename = Str::random(40) . '.' . $ext;
        $path = "{$dir}/{$filename}";

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}
