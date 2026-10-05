<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'goat_id', 'type', 'date', 'cost', 'notes',
        'symptoms', 'diagnosis', 'medication', 'dosage', 'vet_name', 'vaccine_name',
    ];

    protected $casts = [
        'date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }
}
