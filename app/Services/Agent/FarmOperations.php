<?php

namespace App\Services\Agent;

use App\Models\BreedingRecord;
use App\Models\Goat;
use App\Models\HealthRecord;
use App\Models\Sale;
use App\Models\Transaction;
use App\Services\Ai\AiHerdDataCollector;
use App\Services\Ai\RuleBasedAlertService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Whitelisted farm lookups and writes the operational agent is allowed to run.
 * Deletes, sales, and ownership changes stay on their own screens.
 */
class FarmOperations
{
    public function __construct(
        private readonly AiHerdDataCollector $collector = new AiHerdDataCollector,
        private readonly RuleBasedAlertService $alerts = new RuleBasedAlertService,
    ) {}

    /** @param  array<string, mixed>  $args */
    public function run(string $name, array $args): array
    {
        try {
            return match ($name) {
                'herd_summary' => $this->herdSummary(),
                'search_goats' => $this->searchGoats($args),
                'goat_record' => $this->goatRecord($args),
                'care_alerts' => $this->careAlerts(),
                'spend_summary' => $this->spendSummary($args),
                'recent_sales' => $this->recentSales($args),
                'breeding_overview' => $this->breedingOverview(),
                'record_weight' => $this->recordWeight($args),
                'record_health' => $this->recordHealth($args),
                'record_expense' => $this->recordExpense($args),
                default => $this->fail('That farm action is not available.'),
            };
        } catch (ValidationException $e) {
            return $this->fail((string) collect($e->errors())->flatten()->first());
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function declarations(): array
    {
        return [
            $this->decl('herd_summary', 'Counts, breed mix, recent deaths, feed and vet spend, and vaccination gaps for the whole farm.', [
                'focus' => ['type' => 'string', 'description' => 'Optional topic to emphasize, such as feed or breeding.'],
            ]),
            $this->decl('search_goats', 'Find goats by tag, name, or status. Use this before asking for a record you cannot see.', [
                'query' => ['type' => 'string', 'description' => 'Tag or name fragment. Leave empty to list goats.'],
                'status' => ['type' => 'string', 'enum' => ['kid', 'grower', 'breeder', 'sold', 'deceased', 'in_herd']],
            ]),
            $this->decl('goat_record', 'One goat: identity, weight history, health records, breeding, and recent care expenses.', [
                'tag' => ['type' => 'string', 'description' => 'Exact goat tag.'],
            ], ['tag']),
            $this->decl('care_alerts', 'Overdue vaccinations, weight drops, and overdue kidding already calculated from the records.'),
            $this->decl('spend_summary', 'Income and expense totals by category, plus the latest individual entries.', [
                'months' => ['type' => 'integer', 'description' => 'How many months back. Default 3. Maximum 12.'],
            ]),
            $this->decl('recent_sales', 'Latest goat sales with buyer, price, and balance due.', [
                'limit' => ['type' => 'integer', 'description' => 'How many sales. Default 8. Maximum 15.'],
            ]),
            $this->decl('breeding_overview', 'Does currently bred or pregnant, with expected kidding dates.'),
            $this->decl('record_weight', 'Save a weight for a goat. Use only when the user explicitly asks to record it.', [
                'tag' => ['type' => 'string'],
                'weight_kg' => ['type' => 'number'],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD. Defaults to today.'],
                'notes' => ['type' => 'string'],
            ], ['tag', 'weight_kg']),
            $this->decl('record_health', 'Save a vaccination, treatment, checkup, or other health event. Use only when the user explicitly asks to record it.', [
                'tag' => ['type' => 'string'],
                'type' => ['type' => 'string', 'enum' => ['vaccination', 'treatment', 'checkup', 'other']],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD.'],
                'vaccine_name' => ['type' => 'string'],
                'symptoms' => ['type' => 'string'],
                'diagnosis' => ['type' => 'string'],
                'medication' => ['type' => 'string'],
                'dosage' => ['type' => 'string'],
                'vet_name' => ['type' => 'string'],
                'cost' => ['type' => 'number'],
                'notes' => ['type' => 'string'],
            ], ['tag', 'type', 'date']),
            $this->decl('record_expense', 'Save a farm expense. Use only when the user explicitly asks to record it. Categories: '.implode(', ', Transaction::EXPENSE_CATEGORIES).'.', [
                'category' => ['type' => 'string', 'enum' => Transaction::EXPENSE_CATEGORIES],
                'amount' => ['type' => 'number'],
                'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD.'],
                'tag' => ['type' => 'string', 'description' => 'Optional goat tag when the expense belongs to one animal.'],
                'notes' => ['type' => 'string'],
            ], ['category', 'amount', 'date']),
        ];
    }

    /** @param  array<string, mixed>  $properties
     * @param  array<int, string>  $required
     * @return array<string, mixed>
     */
    private function decl(string $name, string $description, array $properties = [], array $required = []): array
    {
        $schema = [
            'name' => $name,
            'description' => $description,
            'parameters' => [
                'type' => 'object',
                'properties' => $properties === [] ? new \stdClass : $properties,
            ],
        ];

        if ($required !== []) {
            $schema['parameters']['required'] = $required;
        }

        return $schema;
    }

    /** @return array{ok: bool, summary: string, data: mixed} */
    private function herdSummary(): array
    {
        $context = $this->collector->farmContext();
        $alertCount = $this->alerts->alerts()->count();

        return $this->ok(
            'Herd size '.$context['herd_size'].', with '.$alertCount.' care alerts.',
            $context + ['care_alert_count' => $alertCount],
        );
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function searchGoats(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        $status = (string) ($args['status'] ?? '');

        $goats = Goat::query()
            ->with('breed:id,name')
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('tag_id', 'like', '%'.$query.'%')
                        ->orWhere('name', 'like', '%'.$query.'%');
                });
            })
            ->when($status === 'in_herd', fn ($q) => $q->inHerd())
            ->when(in_array($status, ['kid', 'grower', 'breeder', 'sold', 'deceased'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('tag_id')
            ->limit(15)
            ->get()
            ->map(fn (Goat $goat) => [
                'tag' => $goat->tag_id,
                'name' => $goat->name,
                'breed' => $goat->breed?->name,
                'gender' => $goat->gender,
                'status' => $goat->status,
                'weight_kg' => $goat->weight_kg !== null ? (float) $goat->weight_kg : null,
            ])
            ->all();

        return $this->ok(count($goats).' goats matched.', $goats);
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function goatRecord(array $args): array
    {
        $goat = $this->findGoat((string) ($args['tag'] ?? $args['tag_id'] ?? ''));
        if (! $goat) {
            return $this->fail('No goat found with that tag.');
        }

        $context = $this->collector->goatContext($goat);

        return $this->ok('Record loaded for '.$goat->tag_id.'.', $context);
    }

    /** @return array{ok: bool, summary: string, data: mixed} */
    private function careAlerts(): array
    {
        $rows = $this->alerts->alerts()->take(30)->values()->all();

        return $this->ok(count($rows).' care alerts.', $rows);
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function spendSummary(array $args): array
    {
        $months = max(1, min(12, (int) ($args['months'] ?? 3)));
        $from = now()->subMonths($months)->toDateString();

        $totals = Transaction::query()
            ->select('type', 'category')
            ->selectRaw('ROUND(SUM(amount), 2) as total, COUNT(*) as entries')
            ->where('date', '>=', $from)
            ->groupBy('type', 'category')
            ->orderBy('type')
            ->orderBy('category')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'category' => $row->category,
                'total' => (float) $row->total,
                'entries' => (int) $row->entries,
            ])
            ->all();

        $latest = Transaction::query()
            ->with('goat:id,tag_id')
            ->where('date', '>=', $from)
            ->orderByDesc('date')
            ->limit(12)
            ->get()
            ->map(fn (Transaction $row) => [
                'date' => $row->date->format('Y-m-d'),
                'type' => $row->type,
                'category' => $row->category,
                'amount' => (float) $row->amount,
                'tag' => $row->goat?->tag_id,
                'notes' => $row->notes,
            ])
            ->all();

        return $this->ok('Spend and income for the last '.$months.' months.', [
            'from' => $from,
            'totals' => $totals,
            'latest' => $latest,
        ]);
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function recentSales(array $args): array
    {
        $limit = max(1, min(15, (int) ($args['limit'] ?? 8)));

        $sales = Sale::query()
            ->with(['goat:id,tag_id,name', 'customer:id,name'])
            ->orderByDesc('sale_date')
            ->limit($limit)
            ->get()
            ->map(fn (Sale $sale) => [
                'date' => $sale->sale_date?->format('Y-m-d'),
                'tag' => $sale->goat?->tag_id,
                'buyer' => $sale->buyer_name,
                'price' => (float) $sale->sale_price,
                'paid' => $sale->paid_amount,
                'balance_due' => $sale->balance_due,
                'status' => $sale->status,
            ])
            ->all();

        return $this->ok(count($sales).' recent sales.', $sales);
    }

    /** @return array{ok: bool, summary: string, data: mixed} */
    private function breedingOverview(): array
    {
        $rows = BreedingRecord::query()
            ->with(['dam:id,tag_id,name', 'sire:id,tag_id,name'])
            ->whereIn('pregnancy_status', ['bred', 'pregnant'])
            ->orderBy('expected_kidding_date')
            ->limit(20)
            ->get()
            ->map(fn (BreedingRecord $row) => [
                'dam' => $row->dam?->tag_id,
                'sire' => $row->sire?->tag_id,
                'status' => $row->pregnancy_status,
                'bred_on' => $row->breeding_date?->format('Y-m-d'),
                'expected_kidding' => $row->expected_kidding_date?->format('Y-m-d'),
            ])
            ->all();

        return $this->ok(count($rows).' active pregnancies.', $rows);
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function recordWeight(array $args): array
    {
        $goat = $this->requireGoat($args);
        $data = Validator::make([
            'weight_kg' => $args['weight_kg'] ?? null,
            'logged_at' => $this->dateOrToday($args['date'] ?? null),
            'notes' => $args['notes'] ?? null,
        ], [
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:300'],
            'logged_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $log = $goat->weightLogs()->create([
            'weight_kg' => $data['weight_kg'],
            'logged_at' => $data['logged_at'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->ok(
            'Recorded '.$log->weight_kg.' kg for '.$goat->tag_id.' on '.$log->logged_at->format('Y-m-d').'.',
            ['tag' => $goat->tag_id, 'weight_kg' => (float) $log->weight_kg, 'date' => $log->logged_at->format('Y-m-d')],
        );
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function recordHealth(array $args): array
    {
        $goat = $this->requireGoat($args);
        $data = Validator::make([
            'type' => $args['type'] ?? null,
            'date' => $this->dateOrToday($args['date'] ?? null),
            'cost' => $args['cost'] ?? 0,
            'notes' => $args['notes'] ?? null,
            'symptoms' => $args['symptoms'] ?? null,
            'diagnosis' => $args['diagnosis'] ?? null,
            'medication' => $args['medication'] ?? null,
            'dosage' => $args['dosage'] ?? null,
            'vet_name' => $args['vet_name'] ?? null,
            'vaccine_name' => $args['vaccine_name'] ?? null,
        ], [
            'type' => ['required', 'in:vaccination,treatment,checkup,other'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'symptoms' => ['nullable', 'string', 'max:2000'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'medication' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:255'],
            'vet_name' => ['nullable', 'string', 'max:255'],
            'vaccine_name' => ['nullable', 'string', 'max:255'],
        ])->validate();

        /** @var HealthRecord $record */
        $record = $goat->healthRecords()->create([
            'type' => $data['type'],
            'date' => $data['date'],
            'cost' => $data['cost'] ?? 0,
            'notes' => $data['notes'] ?? null,
            'symptoms' => $data['symptoms'] ?? null,
            'diagnosis' => $data['diagnosis'] ?? null,
            'medication' => $data['medication'] ?? null,
            'dosage' => $data['dosage'] ?? null,
            'vet_name' => $data['vet_name'] ?? null,
            'vaccine_name' => $data['vaccine_name'] ?? null,
        ]);

        return $this->ok(
            'Recorded a '.$record->type.' for '.$goat->tag_id.' on '.$record->date->format('Y-m-d').'.',
            ['tag' => $goat->tag_id, 'type' => $record->type, 'date' => $record->date->format('Y-m-d')],
        );
    }

    /** @param  array<string, mixed>  $args
     * @return array{ok: bool, summary: string, data: mixed}
     */
    private function recordExpense(array $args): array
    {
        $goat = null;
        $tag = trim((string) ($args['tag'] ?? $args['tag_id'] ?? ''));
        if ($tag !== '') {
            $goat = $this->findGoat($tag);
            if (! $goat) {
                return $this->fail('No goat found with that tag.');
            }
        }

        $data = Validator::make([
            'category' => $args['category'] ?? null,
            'amount' => $args['amount'] ?? null,
            'date' => $this->dateOrToday($args['date'] ?? null),
            'notes' => $args['notes'] ?? null,
        ], [
            'category' => ['required', 'in:'.implode(',', Transaction::EXPENSE_CATEGORIES)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $transaction = Transaction::create([
            'type' => 'expense',
            'category' => $data['category'],
            'amount' => $data['amount'],
            'date' => $data['date'],
            'goat_id' => $goat?->id,
            'notes' => $data['notes'] ?? null,
        ]);

        $who = $goat ? ' for '.$goat->tag_id : '';

        return $this->ok(
            'Recorded '.$transaction->category.' expense of '.$transaction->amount.$who.'.',
            [
                'category' => $transaction->category,
                'amount' => (float) $transaction->amount,
                'date' => $transaction->date->format('Y-m-d'),
                'tag' => $goat?->tag_id,
            ],
        );
    }

    /** @param  array<string, mixed>  $args */
    private function requireGoat(array $args): Goat
    {
        $goat = $this->findGoat((string) ($args['tag'] ?? $args['tag_id'] ?? ''));
        if (! $goat) {
            throw ValidationException::withMessages(['tag' => 'No goat found with that tag.']);
        }

        return $goat;
    }

    private function findGoat(string $tag): ?Goat
    {
        $tag = trim($tag);
        if ($tag === '') {
            return null;
        }

        return Goat::query()
            ->whereRaw('LOWER(tag_id) = ?', [mb_strtolower($tag)])
            ->first();
    }

    private function dateOrToday(mixed $date): string
    {
        $value = trim((string) $date);
        if ($value === '' || strcasecmp($value, 'today') === 0) {
            return now()->toDateString();
        }

        return $value;
    }

    /** @return array{ok: bool, summary: string, data: mixed} */
    private function ok(string $summary, mixed $data): array
    {
        return ['ok' => true, 'summary' => $summary, 'data' => $data];
    }

    /** @return array{ok: bool, summary: string, data: null} */
    private function fail(string $summary): array
    {
        return ['ok' => false, 'summary' => $summary, 'data' => null];
    }
}
