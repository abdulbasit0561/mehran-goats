<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Caretaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'phone', 'every_nth_kid', 'start_date', 'active', 'notes',
    ];

    protected $casts = [
        'every_nth_kid' => 'integer',
        'start_date'    => 'date',
        'active'        => 'boolean',
    ];

    //---------------------------------------------------------------------
    // Relationships
    //---------------------------------------------------------------------

    public function goats(): HasMany
    {
        return $this->hasMany(Goat::class)->orderBy('dob');
    }

    //---------------------------------------------------------------------
    // Birth ledger — the core "every Nth kid" calculation
    //---------------------------------------------------------------------

    /**
     * Walks all breeding records that have a kidding_date, ordered chronologically,
     * and builds a ledger of every kid born on the farm.
     *
     * Each entry: { record, kidding_date, litter_size, kid_start, kid_end, share_count }
     * where kid_start/kid_end are the cumulative kid numbers for this kidding event,
     * and share_count is how many kids from this litter belong to this caretaker.
     *
     * @return Collection<int, object>
     */
    public function birthLedger(): Collection
    {
        $nth = max(1, (int) $this->every_nth_kid);

        $records = BreedingRecord::with(['sire', 'dam'])
            ->whereNotNull('kidding_date')
            ->where('litter_size', '>', 0)
            ->orderBy('kidding_date')
            ->orderBy('id')
            ->get();

        $ledger = collect();
        $running = 0;

        foreach ($records as $record) {
            $litter = (int) $record->litter_size;
            if ($litter <= 0) {
                continue;
            }

            $start = $running + 1;
            $end   = $running + $litter;

            // Count how many of this litter's kids are on an Nth boundary
            $shareCount = 0;
            for ($i = $start; $i <= $end; $i++) {
                if ($i % $nth === 0) {
                    $shareCount++;
                }
            }

            $ledger->push((object) [
                'record'       => $record,
                'kidding_date' => $record->kidding_date,
                'litter_size'  => $litter,
                'kid_start'    => $start,
                'kid_end'      => $end,
                'share_count'  => $shareCount,
            ]);

            $running = $end;
        }

        return $ledger;
    }

    //---------------------------------------------------------------------
    // Summary stats (attributes)
    //---------------------------------------------------------------------

    /** Total kids born on the farm (sum of all litter_sizes from kidding events). */
    public function getTotalKidsBornAttribute(): int
    {
        return (int) BreedingRecord::whereNotNull('kidding_date')
            ->where('litter_size', '>', 0)
            ->sum('litter_size');
    }

    /** How many kids the caretaker is owed so far (floor(total / every_nth_kid)). */
    public function getKidsOwedAttribute(): int
    {
        $nth = max(1, (int) $this->every_nth_kid);
        return intdiv($this->total_kids_born, $nth);
    }

    /** How many goats have been manually assigned to this caretaker. */
    public function getKidsAssignedAttribute(): int
    {
        return $this->goats()->count();
    }

    /** Kids owed minus kids assigned = how many still pending to be handed over. */
    public function getKidsPendingAttribute(): int
    {
        return max(0, $this->kids_owed - $this->kids_assigned);
    }

    /** The cumulative kid number at which the caretaker gets their next goat. */
    public function getNextShareAtKidAttribute(): int
    {
        $nth = max(1, (int) $this->every_nth_kid);
        return ($this->kids_owed + 1) * $nth;
    }
}
