<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfitDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id', 'period_start', 'period_end', 'total_profit',
        'share_pct', 'amount', 'status', 'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'total_profit' => 'decimal:2',
        'share_pct'    => 'decimal:2',
        'amount'       => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
