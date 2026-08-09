<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exchange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'exchange_date',
        'outgoing_goat_id',
        'outgoing_value',
        'incoming_goat_id',
        'incoming_tag_id',
        'breed_id',
        'incoming_gender',
        'incoming_weight_kg',
        'incoming_value',
        'counterparty',
        'cash_direction',
        'cash_amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'exchange_date'     => 'date',
        'outgoing_value'    => 'decimal:2',
        'incoming_value'    => 'decimal:2',
        'incoming_weight_kg'=> 'decimal:2',
        'cash_amount'       => 'decimal:2',
    ];

    public function outgoingGoat(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'outgoing_goat_id');
    }

    public function incomingGoat(): BelongsTo
    {
        return $this->belongsTo(Goat::class, 'incoming_goat_id');
    }

    public function breed(): BelongsTo
    {
        return $this->belongsTo(Breed::class);
    }

    /** Net value change of the herd from this exchange (incoming - outgoing). */
    public function getNetValueChangeAttribute(): float
    {
        return round((float) $this->incoming_value - (float) $this->outgoing_value, 2);
    }
}
