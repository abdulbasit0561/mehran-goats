<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'phone', 'email', 'address', 'notes'];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
