<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static Builder income()
 * @method static Builder expense()
 * @method static Builder between(string $start, string $end)
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'category', 'amount', 'date', 'goat_id', 'investor_id',
        'payment_method', 'is_recurring', 'budget_amount', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'date'         => 'date',
        'is_recurring' => 'boolean',
        'budget_amount'=> 'decimal:2',
    ];

    /** Expense categories matching the Google Sheet. */
    public const EXPENSE_CATEGORIES = [
        'Feed & Fodder',
        'Veterinary & Medicine',
        'Labor / Wages',
        'Shelter & Maintenance',
        'Transport',
        'Breeding Costs',
        'Utilities',
        'Equipment & Tools',
        'Goat Purchases',
        'Other',
    ];

    /** Income categories. */
    public const INCOME_CATEGORIES = [
        'Goat Sales',
        'Milk Sales',
        'Manure / By-products',
        'Other Income',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    // ---- Scopes -----------------------------------------------------------

    public function scopeIncome(Builder $query): Builder
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query): Builder
    {
        return $query->where('type', 'expense');
    }

    public function scopeBetween(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // ---- Accessors --------------------------------------------------------

    /** Variance = budget_amount - amount (positive = under budget). */
    public function getVarianceAttribute(): ?float
    {
        if ($this->budget_amount === null) {
            return null;
        }
        return round((float) $this->budget_amount - (float) $this->amount, 2);
    }
}
