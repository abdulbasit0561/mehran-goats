<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Investor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'role', 'expense_split_pct', 'email', 'phone', 'address', 'notes'];

    protected $casts = [
        'expense_split_pct' => 'decimal:2',
    ];

    public function investments(): HasMany
    {
        return $this->hasMany(Investment::class);
    }

    public function ownershipUnits(): HasMany
    {
        return $this->hasMany(OwnershipUnit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function profitDistributions(): HasMany
    {
        return $this->hasMany(ProfitDistribution::class)->orderByDesc('period_start');
    }

    /** Total capital this investor has contributed. */
    public function getTotalCapitalAttribute(): string
    {
        return (string) $this->investments()->sum('amount');
    }

    /**
     * This investor's share percentage across the entire farm, calculated as
     *   investor_capital / total_capital * 100
     * Returns 0 when no capital has been invested farm-wide.
     */
    public function getSharePctAttribute(): float
    {
        $totalCapital = (float) DB::table('investments')->sum('amount');
        if ($totalCapital <= 0) {
            return 0;
        }
        $mine = (float) $this->investments()->sum('amount');
        return round($mine / $totalCapital * 100, 2);
    }

    /** Share of current herd value based on ownership %. */
    public function getHerdShareAttribute(): float
    {
        $totalHerdValue = (float) Goat::where('status', '!=', 'sold')
            ->where('status', '!=', 'deceased')
            ->sum('estimated_value');
        if ($totalHerdValue <= 0) {
            return 0;
        }
        return round($totalHerdValue * $this->share_pct / 100, 2);
    }

    /** Share of realized profit based on ownership %. */
    public function getProfitShareAttribute(): float
    {
        $realizedProfit = (float) Goat::whereIn('status', ['sold'])
            ->selectRaw('SUM(sale_price - purchase_price) as total')
            ->value('total');
        if ($realizedProfit <= 0) {
            return 0;
        }
        return round($realizedProfit * $this->share_pct / 100, 2);
    }

    /** Total expenses owed by this investor based on their expense split %. */
    public function getExpenseOwedAttribute(): float
    {
        $totalExpenses = (float) Transaction::expense()->sum('amount');
        return round($totalExpenses * (float) $this->expense_split_pct / 100, 2);
    }

    /** Total expenses actually paid by this investor. */
    public function getExpensePaidAttribute(): float
    {
        return (float) $this->transactions()->expense()->sum('amount');
    }

    /** Settlement balance: Paid - Owed. Positive = owed money, negative = owes pool. */
    public function getSettlementBalanceAttribute(): float
    {
        return round($this->expense_paid - $this->expense_owed, 2);
    }

    /** Check if this investor is the manager/fund holder. */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }
}
