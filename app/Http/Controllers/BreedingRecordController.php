<?php

namespace App\Http\Controllers;

use App\Models\BreedingRecord;
use App\Models\Caretaker;
use App\Models\Goat;
use Illuminate\Http\Request;

class BreedingRecordController extends Controller
{
    public function index(Goat $goat)
    {
        $records = $goat->siredRecords()->orWhere('dam_id', $goat->id)->latest('breeding_date')->paginate(20);

        return view('breeding-records.index', compact('goat', 'records'));
    }

    public function create(Goat $goat)
    {
        $sires = Goat::where('gender', 'male')->whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);
        $dams  = Goat::where('gender', 'female')->whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);

        return view('breeding-records.create', compact('goat', 'sires', 'dams'));
    }

    public function store(Request $request, Goat $goat)
    {
        $validated = $request->validate([
            'sire_id'       => ['required', 'exists:goats,id'],
            'dam_id'        => ['required', 'exists:goats,id', 'different:sire_id'],
            'breeding_date' => ['required', 'date', 'before_or_equal:today'],
            'kidding_date'  => ['nullable', 'date', 'after_or_equal:breeding_date'],
            'litter_size'   => ['nullable', 'integer', 'min:0', 'max:20'],
            'notes'         => ['nullable', 'string'],
        ]);

        // Auto-calculate expected kidding date (breeding_date + 150 days)
        $kiddingDate = $validated['kidding_date'] ?? null;
        $validated['expected_kidding_date'] = $kiddingDate
            ? null // actual kidding recorded — no need for "expected"
            : \Carbon\Carbon::parse($validated['breeding_date'])->addDays(BreedingRecord::GESTATION_DAYS);

        // Set initial pregnancy status
        if ($kiddingDate) {
            $validated['pregnancy_status'] = 'kidded';
        } else {
            $validated['pregnancy_status'] = 'bred';
        }
        if (empty($validated['litter_size'])) {
            $validated['litter_size'] = 0;
        }

        BreedingRecord::create([
            'sire_id'               => $validated['sire_id'],
            'dam_id'                => $validated['dam_id'],
            'breeding_date'         => $validated['breeding_date'],
            'kidding_date'          => $validated['kidding_date'] ?? null,
            'expected_kidding_date' => $validated['expected_kidding_date'] ?? null,
            'litter_size'           => $validated['litter_size'],
            'pregnancy_status'      => $validated['pregnancy_status'],
            'notes'                 => $validated['notes'] ?? null,
        ]);

        return redirect()
            ->route('goats.show', $goat)
            ->with('success', 'Breeding record added. Expected kidding date: '
                . \Carbon\Carbon::parse($validated['breeding_date'])->addDays(BreedingRecord::GESTATION_DAYS)->format('M d, Y'));
    }

    //---------------------------------------------------------------------
    // EDIT / UPDATE (new — previously only create/destroy existed)
    //---------------------------------------------------------------------

    public function edit(BreedingRecord $breeding_record)
    {
        $breeding_record->load(['sire', 'dam']);
        $sires = Goat::where('gender', 'male')->whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);
        $dams  = Goat::where('gender', 'female')->whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);

        return view('breeding-records.edit', compact('breeding_record', 'sires', 'dams'));
    }

    public function update(Request $request, BreedingRecord $breeding_record)
    {
        $validated = $request->validate([
            'sire_id'       => ['required', 'exists:goats,id'],
            'dam_id'        => ['required', 'exists:goats,id', 'different:sire_id'],
            'breeding_date' => ['required', 'date', 'before_or_equal:today'],
            'kidding_date'  => ['nullable', 'date', 'after_or_equal:breeding_date'],
            'litter_size'   => ['nullable', 'integer', 'min:0', 'max:20'],
            'notes'         => ['nullable', 'string'],
        ]);

        $validated['expected_kidding_date'] = ($validated['kidding_date'] ?? null)
            ? null
            : \Carbon\Carbon::parse($validated['breeding_date'])->addDays(BreedingRecord::GESTATION_DAYS);

        if (($validated['kidding_date'] ?? null)) {
            $validated['pregnancy_status'] = 'kidded';
        } elseif ($breeding_record->pregnancy_status === 'pregnant') {
            // keep confirmed-pregnant status
            $validated['pregnancy_status'] = 'pregnant';
        } else {
            $validated['pregnancy_status'] = 'bred';
        }
        $validated['litter_size'] = $validated['litter_size'] ?? 0;

        $breeding_record->update($validated);

        return redirect()
            ->route('goats.show', $breeding_record->dam_id)
            ->with('success', 'Breeding record updated.');
    }

    //---------------------------------------------------------------------
    // PREGNANCY STATUS ACTIONS
    //---------------------------------------------------------------------

    /** Mark a breeding record as pregnancy-confirmed. */
    public function markPregnant(BreedingRecord $breeding_record)
    {
        if ($breeding_record->pregnancy_status === 'kidded') {
            return back()->with('error', 'Cannot mark as pregnant — this doe has already kidded.');
        }

        $breeding_record->update(['pregnancy_status' => 'pregnant']);

        return redirect()
            ->route('goats.show', $breeding_record->dam_id)
            ->with('success', "Pregnancy confirmed for {$breeding_record->dam->tag_id}. Expected kidding: {$breeding_record->expected_kidding_date->format('M d, Y')}");
    }

    /** Mark as aborted / open (did not conceive). */
    public function markOpen(BreedingRecord $breeding_record)
    {
        $breeding_record->update(['pregnancy_status' => 'open']);

        return redirect()
            ->route('goats.show', $breeding_record->dam_id)
            ->with('success', "Marked as open/did-not-conceive for {$breeding_record->dam->tag_id}.");
    }

    //---------------------------------------------------------------------
    // RECORD KIDDING — the key action
    // Records the birth and auto-creates kid Goat records with parent lineage.
    // Also checks the caretaker "every Nth kid" share.
    //---------------------------------------------------------------------

    public function recordKidding(BreedingRecord $breeding_record)
    {
        // Don't allow if already kidded and has kids
        if ($breeding_record->kids()->exists()) {
            return redirect()
                ->route('goats.show', $breeding_record->dam_id)
                ->with('error', 'Kids have already been registered for this breeding record.');
        }

        $breeding_record->load(['sire.breed', 'dam.breed']);
        $sires = Goat::where('gender', 'male')->whereNotIn('status', ['sold', 'deceased'])->orderBy('tag_id')->get(['id', 'tag_id', 'name']);

        return view('breeding-records.kidding', compact('breeding_record', 'sires'));
    }

    public function storeKidding(Request $request, BreedingRecord $breeding_record)
    {
        // Guard: don't allow re-kidding an already-kidded record
        if ($breeding_record->kids()->exists()) {
            return redirect()
                ->route('goats.show', $breeding_record->dam_id)
                ->with('error', 'Kids have already been registered for this breeding record.');
        }

        $validated = $request->validate([
            'kidding_date'       => ['required', 'date', 'after_or_equal:' . $breeding_record->breeding_date->format('Y-m-d')],
            'litter_size'        => ['required', 'integer', 'min:1', 'max:10'],
            'kidding_difficulty' => ['nullable', 'in:easy,assisted,difficult,cesarean'],
            'notes'              => ['nullable', 'string'],
            'kids'               => ['required', 'array', 'size:' . $request->input('litter_size')],
            'kids.*.tag_id'      => ['required', 'string', 'max:100', 'distinct', 'unique:goats,tag_id'],
            'kids.*.gender'      => ['required', 'in:male,female'],
            'kids.*.name'        => ['nullable', 'string', 'max:100'],
            'kids.*.weight_kg'   => ['nullable', 'numeric', 'min:0', 'max:50'],
        ]);

        $dam = $breeding_record->dam;
        $sire = $breeding_record->sire;
        $breedId = $dam->breed_id;

        // Determine the running kid count for caretaker "every Nth kid" logic
        $totalKidsBefore = (int) BreedingRecord::whereNotNull('kidding_date')
            ->where('litter_size', '>', 0)
            ->sum('litter_size');

        // Get the active caretaker (if any)
        $caretaker = Caretaker::where('active', true)->first();

        // Create each kid goat
        foreach ($validated['kids'] as $index => $kidData) {
            $globalKidNumber = $totalKidsBefore + $index + 1;

            $goatData = [
                'tag_id'             => $kidData['tag_id'],
                'name'               => $kidData['name'] ?? null,
                'breed_id'           => $breedId,
                'gender'             => $kidData['gender'],
                'dob'                => $validated['kidding_date'],
                'weight_kg'          => $kidData['weight_kg'] ?? 0,
                'status'             => 'kid',
                'sire_id'            => $sire->id,
                'dam_id'             => $dam->id,
                'breeding_record_id' => $breeding_record->id,
                'birth_type'         => 'farm_born',
                'estimated_value'    => 0,
                'purchase_price'     => 0,
            ];

            // Check caretaker share: every Nth kid
            if ($caretaker) {
                $nth = max(1, (int) $caretaker->every_nth_kid);
                if ($globalKidNumber % $nth === 0) {
                    $goatData['caretaker_id'] = $caretaker->id;
                }
            }

            Goat::create($goatData);
        }

        // Update the breeding record
        $kiddingNotes = $validated['notes'] ?? null;
        $breeding_record->update([
            'kidding_date'       => $validated['kidding_date'],
            'litter_size'        => $validated['litter_size'],
            'kidding_difficulty' => $validated['kidding_difficulty'] ?? null,
            'pregnancy_status'   => 'kidded',
            'expected_kidding_date' => null,
            'notes'              => $kiddingNotes
                ? ($breeding_record->notes . "\n--- Kidding ---\n" . $kiddingNotes)
                : $breeding_record->notes,
        ]);

        $message = "Kidding recorded! {$validated['litter_size']} kid(s) born and registered.";

        // Note caretaker assignment
        if ($caretaker) {
            $assignedCount = $breeding_record->fresh()->kids()->where('caretaker_id', $caretaker->id)->count();
            if ($assignedCount > 0) {
                $message .= " {$assignedCount} kid(s) auto-assigned to caretaker {$caretaker->name} (every {$caretaker->every_nth_kid}th kid).";
            }
        }

        return redirect()
            ->route('goats.show', $dam->id)
            ->with('success', $message);
    }

    public function destroy(BreedingRecord $breeding_record)
    {
        $goatId = $breeding_record->sire_id;
        $breeding_record->delete();

        return redirect()
            ->route('goats.show', $goatId)
            ->with('success', 'Breeding record deleted.');
    }
}
