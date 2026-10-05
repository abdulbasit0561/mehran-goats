<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightLog extends Model
{
    use HasFactory;

    protected $fillable = ['goat_id', 'weight_kg', 'logged_at', 'notes'];

    protected $casts = [
        'weight_kg' => 'decimal:2',
        'logged_at' => 'date',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }

    protected static function booted(): void
    {
        // Keep the goat's current-weight snapshot in sync no matter how a
        // weight log is created or removed (HTTP form, seeder, tinker...).
        static::created(function (WeightLog $log) {
            $log->goat?->syncWeightFromLogs();
        });

        static::deleted(function (WeightLog $log) {
            $log->goat?->syncWeightFromLogs();
        });
    }
}
