<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'goat_id', 'customer_id', 'seller_investor_id',
        'sale_date', 'sale_price', 'sale_weight',
        'payment_method', 'photo_path', 'notes', 'status',
    ];

    protected $casts = [
        'sale_date'   => 'date',
        'sale_price'  => 'decimal:2',
        'sale_weight' => 'decimal:2',
        'deleted_at'  => 'datetime',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Investor::class, 'seller_investor_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class)->orderByDesc('payment_date');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getBuyerNameAttribute(): string
    {
        return $this->customer?->name ?? 'Unknown / walk-in';
    }

    /** Amount actually paid so far (payments arrive in Phase 3). */
    public function getPaidAmountAttribute(): float
    {
        return round((float) ($this->payments()->sum('amount') ?? 0), 2);
    }

    public function getBalanceDueAttribute(): float
    {
        return round(max((float) $this->sale_price - $this->paid_amount, 0), 2);
    }

    /** Recompute unpaid/partial/paid from recorded payments. */
    public function refreshPaymentStatus(): void
    {
        $paid = $this->paid_amount;
        $price = (float) $this->sale_price;

        $this->status = $paid <= 0
            ? 'unpaid'
            : ($paid + 0.001 >= $price ? 'paid' : 'partial');
        $this->save();
    }
}
