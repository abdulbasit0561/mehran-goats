<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YearlyClearance extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'clearance_date',
        'total_sales',
        'total_purchases',
        'total_expenses',
        'total_expenses_business',
        'gross_profit',
        'net_profit',
        'herd_value_opening',
        'herd_value_closing',
        'outstanding_business_balance',
        'report_payload',
        'completed_by',
        'completed_at',
    ];

    protected $casts = [
        'year'                => 'integer',
        'clearance_date'      => 'date',
        'total_sales'         => 'decimal:2',
        'total_purchases'     => 'decimal:2',
        'total_expenses'      => 'decimal:2',
        'total_expenses_business' => 'decimal:2',
        'gross_profit'        => 'decimal:2',
        'net_profit'          => 'decimal:2',
        'herd_value_opening'  => 'decimal:2',
        'herd_value_closing'  => 'decimal:2',
        'outstanding_business_balance' => 'decimal:2',
        'report_payload'      => 'array',
        'completed_at'        => 'datetime',
    ];

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(YearlyClearancePartner::class);
    }

    /** The clearance for the previous financial year, if one exists. */
    public function previousClearance(): ?self
    {
        return static::where('year', $this->year - 1)->first();
    }

    /** Human label: "FY 2026". */
    public function getPeriodLabelAttribute(): string
    {
        return "FY {$this->year}";
    }
}
