<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BreedingRecord extends Model
{
    use HasFactory;

    /** Standard goat gestation period in days (Pakistan breeds: Beetal, Teddy, Kamori ≈ 149–150 days) */
    public const GESTATION_DAYS = 150;

    protected $fillable = [
        'sire_id', 'dam_id', 'breeding_date', 'kidding_date',
        'expected_kidding_date', 'pregnancy_status', 'litter_size',
        'kidding_difficulty', 'notes',
    ];

    protected $casts = [
        'breeding_date'         => 'date',
        'kidding_date'          => 'date',
        'expected_kidding_date' => 'date',
        'litter_size'           => 'integer',
    ];

    //---------------------------------------------------------------------
    // Relationships
    //---------------------------------------------------------------------

    public function sire(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'sire_id');
    }

    public function dam(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'dam_id');
    }

    /** Kids born from this specific breeding/kidding event. */
    public function kids(): HasMany
    {
        return $this->hasMany(Goat::class, 'breeding_record_id')->orderBy('tag_id');
    }

    //---------------------------------------------------------------------
    // Pregnancy logic
    //---------------------------------------------------------------------

    /**
     * Expected kidding date: explicitly set, or auto-calculated from breeding_date.
     * Uses 150-day gestation standard for Pakistani goat breeds.
     */
    public function getExpectedKiddingDateAttribute(): ?Carbon
    {
        $value = $this->attributes['expected_kidding_date'] ?? null;
        if ($value) {
            return Carbon::parse($value);
        }
        if ($this->breeding_date) {
            return $this->breeding_date->copy()->addDays(self::GESTATION_DAYS);
        }
        return null;
    }

    /** Is the doe currently pregnant (bred or confirmed pregnant, not yet kidded)? */
    public function isPregnant(): bool
    {
        return in_array($this->pregnancy_status, ['bred', 'pregnant']);
    }

    /** Is pregnancy confirmed by the farmer? */
    public function isConfirmed(): bool
    {
        return $this->pregnancy_status === 'pregnant';
    }

    /** Days since breeding — how far along the pregnancy is. */
    public function getDaysPregnantAttribute(): int
    {
        if (! $this->breeding_date) {
            return 0;
        }
        $end = $this->kidding_date ?? now();
        return max(0, (int) $this->breeding_date->diffInDays($end));
    }

    /** Days until expected kidding (negative = overdue). */
    public function getDaysUntilDueAttribute(): int
    {
        if (! $this->expected_kidding_date) {
            return 0;
        }
        return (int) now()->startOfDay()->diffInDays($this->expected_kidding_date, false);
    }

    /** Human-friendly pregnancy progress (0–100%). */
    public function getPregnancyProgressAttribute(): int
    {
        if ($this->pregnancy_status === 'kidded' || ! $this->breeding_date) {
            return 100;
        }
        $progress = ($this->days_pregnant / self::GESTATION_DAYS) * 100;
        return (int) min(100, max(0, round($progress)));
    }

    //---------------------------------------------------------------------
    // Scopes
    //---------------------------------------------------------------------

    public function scopePregnant($query)
    {
        return $query->whereIn('pregnancy_status', ['bred', 'pregnant']);
    }

    public function scopeKidded($query)
    {
        return $query->where('pregnancy_status', 'kidded');
    }

    /** Kidding due within N days (default 30). */
    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->pregnant()
            ->whereNotNull('expected_kidding_date')
            ->where('expected_kidding_date', '<=', now()->addDays($days));
    }

    public function scopeOverdue($query)
    {
        return $query->pregnant()
            ->where('expected_kidding_date', '<', now());
    }
}
