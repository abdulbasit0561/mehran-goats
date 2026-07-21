<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoatImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'goat_id',
        'image_path',
        'age_months',
        'weight_kg',
        'taken_at',
        'notes',
    ];

    protected $casts = [
        'age_months' => 'decimal:1',
        'weight_kg'  => 'decimal:2',
        'taken_at'   => 'date',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }

    /**
     * Human-friendly label for the photo's recorded date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->taken_at?->format('M d, Y') ?? '—';
    }
}
