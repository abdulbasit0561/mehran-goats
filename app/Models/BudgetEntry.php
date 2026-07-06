<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'type', 'category', 'year',
        'jan', 'feb', 'mar', 'apr', 'may', 'jun',
        'jul', 'aug', 'sep', 'oct', 'nov', 'dec',
    ];

    protected $casts = [
        'jan' => 'decimal:2', 'feb' => 'decimal:2', 'mar' => 'decimal:2',
        'apr' => 'decimal:2', 'may' => 'decimal:2', 'jun' => 'decimal:2',
        'jul' => 'decimal:2', 'aug' => 'decimal:2', 'sep' => 'decimal:2',
        'oct' => 'decimal:2', 'nov' => 'decimal:2', 'dec' => 'decimal:2',
    ];

    public const MONTHS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];

    public const INCOME_CATEGORIES = [
        'Goat Sales', 'Milk Sales', 'Manure / By-products', 'Other Income',
    ];

    public const EXPENSE_CATEGORIES = [
        'Feed & Fodder', 'Veterinary & Medicine', 'Labor / Wages',
        'Shelter & Maintenance', 'Transport', 'Breeding Costs',
        'Insurance', 'Utilities', 'Equipment & Tools',
        'Goat Purchases', 'Miscellaneous',
    ];

    /** Annual total for this row. */
    public function getAnnualTotalAttribute(): float
    {
        return round(array_sum(array_map(fn ($m) => (float) $this->$m, self::MONTHS)), 2);
    }

    /** Scope by year. */
    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }
}
