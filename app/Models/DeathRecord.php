<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeathRecord extends Model
{
    protected $fillable = [
        'goat_id',
        'death_date',
        'cause',
        'disposal_method',
        'disposal_value',
        'notes',
    ];

    protected $casts = [
        'death_date'      => 'date',
        'disposal_value'  => 'decimal:2',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }
}
