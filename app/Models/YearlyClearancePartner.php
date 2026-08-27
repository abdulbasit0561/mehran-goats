<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YearlyClearancePartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'yearly_clearance_id',
        'investor_id',
        'investor_name',
        'share_pct',
        'opening_balance',
        'investment',
        'profit_share',
        'expenses_paid_by_partner',
        'withdrawals_received',
        'final_balance',
        'status',
    ];

    protected $casts = [
        'share_pct'                => 'decimal:2',
        'opening_balance'          => 'decimal:2',
        'investment'               => 'decimal:2',
        'profit_share'             => 'decimal:2',
        'expenses_paid_by_partner' => 'decimal:2',
        'withdrawals_received'     => 'decimal:2',
        'final_balance'            => 'decimal:2',
    ];

    public function clearance(): BelongsTo
    {
        return $this->belongsTo(YearlyClearance::class, 'yearly_clearance_id');
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    /** Display label for the settlement status. */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'payable_to_partner'      => 'Payable to Partner',
            'receivable_from_partner' => 'Receivable from Partner',
            default                   => 'Settled',
        };
    }
}
