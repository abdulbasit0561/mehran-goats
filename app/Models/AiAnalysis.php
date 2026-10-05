<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope', 'goat_id',
        'insights', 'suggestions', 'alerts', 'recommendations',
        'model', 'generated_at',
    ];

    protected $casts = [
        'insights'        => 'array',
        'suggestions'     => 'array',
        'alerts'          => 'array',
        'recommendations' => 'array',
        'generated_at'    => 'datetime',
    ];

    public function goat(): BelongsTo
    {
        return $this->belongsTo(Goat::class);
    }

    public function scopeFarm($query)
    {
        return $query->where('scope', 'farm');
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('generated_at');
    }
}
