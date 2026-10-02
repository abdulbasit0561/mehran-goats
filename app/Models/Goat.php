<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Goat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tag_id', 'name', 'breed_id', 'caretaker_id', 'gender', 'dob', 'weight_kg',
        'purchase_date', 'purchase_weight', 'vendor',
        'status', 'purchase_price', 'sale_price',
        'sale_date', 'sale_weight', 'estimated_value',
        'sire_id', 'dam_id', 'breeding_record_id', 'birth_type',
        'photo', 'notes',
    ];

    protected $casts = [
        'dob'             => 'date',
        'purchase_date'   => 'date',
        'sale_date'       => 'date',
        'weight_kg'       => 'decimal:2',
        'purchase_weight' => 'decimal:2',
        'sale_weight'     => 'decimal:2',
        'purchase_price'  => 'decimal:2',
        'sale_price'      => 'decimal:2',
        'estimated_value' => 'decimal:2',
    ];

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(Caretaker::class);
    }

    /** Gallery images for this goat (newest first). */
    public function images(): HasMany
    {
        return $this->hasMany(GoatImage::class)->orderByDesc('taken_at');
    }

    public function healthRecords(): HasMany
    {
        return $this->hasMany(HealthRecord::class)->orderByDesc('date');
    }

    public function ownershipUnits(): HasMany
    {
        return $this->hasMany(OwnershipUnit::class);
    }

    /** Financial transactions linked to this goat (purchases, sales, etc.). */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /** The sale record for this goat (one-to-one; a goat is sold once). */
    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class);
    }

    /** The death record for this goat (one-to-one; a goat can only die once). */
    public function deathRecord(): HasOne
    {
        return $this->hasOne(DeathRecord::class);
    }

    /** As sire (male parent) */
    public function siredRecords(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'sire_id');
    }

    /** As dam (female parent) */
    public function birthedRecords(): HasMany
    {
        return $this->hasMany(BreedingRecord::class, 'dam_id');
    }

    /** Father of this goat (if farm-born). */
    public function sire(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'sire_id');
    }

    /** Mother of this goat (if farm-born). */
    public function dam(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'dam_id');
    }

    /** The birth event this goat was born from. */
    public function breedingRecord(): BelongsTo
    {
        return $this->belongsTo(BreedingRecord::class);
    }

    /** Kids born to this doe. */
    public function offspring(): HasMany
    {
        return $this->hasMany(Goat::class, 'dam_id')->orderBy('dob');
    }

    /** Is this goat farm-born (not purchased)? */
    public function isFarmBorn(): bool
    {
        return $this->birth_type === 'farm_born';
    }

    /** Get the active pregnancy (most recent bred/pregnant record for this doe). */
    public function activePregnancy(): ?BreedingRecord
    {
        return $this->birthedRecords()
            ->whereIn('pregnancy_status', ['bred', 'pregnant'])
            ->latest('breeding_date')
            ->first();
    }

    /** Age in months */
    public function getAgeInMonthsAttribute(): ?float
    {
        if (! $this->dob) {
            return null;
        }
        return round($this->dob->diffInMonths(now()), 1);
    }

    /** Realized gain/loss: sale_price - purchase_price (0 if not sold). */
    public function getRealizedGainLossAttribute(): float
    {
        if ($this->status !== 'sold') {
            return 0;
        }
        return round((float) $this->sale_price - (float) $this->purchase_price, 2);
    }

    /** Days held from purchase date to sale date (or today if still in herd). */
    public function getDaysHeldAttribute(): ?int
    {
        if (! $this->purchase_date) {
            return null;
        }
        $end = $this->sale_date ?? now();
        return (int) $this->purchase_date->diffInDays($end);
    }

    /** Current value: estimated_value (or purchase_price fallback) if in herd, sale_price if sold, 0 if deceased. */
    public function getCurrentValueAttribute(): float
    {
        if ($this->status === 'sold') {
            return (float) $this->sale_price;
        }
        if ($this->status === 'deceased') {
            return 0;
        }
        // Fall back to purchase_price when estimated_value is not set
        return (float) ($this->estimated_value > 0 ? $this->estimated_value : $this->purchase_price);
    }

    /** Scope: goats currently in the herd (not sold, not deceased). */
    public function scopeInHerd($query)
    {
        return $query->whereNotIn('status', ['sold', 'deceased']);
    }

    /** Scope: sold goats. */
    public function scopeSold($query)
    {
        return $query->where('status', 'sold');
    }

    /** Scope: deceased goats. */
    public function scopeDeceased($query)
    {
        return $query->where('status', 'deceased');
    }
}
