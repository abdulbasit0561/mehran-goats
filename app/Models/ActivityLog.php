<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'action', 'entity_type', 'entity_id',
        'description', 'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable action label with colour mapping.
     */
    public function getActionBadgeAttribute(): array
    {
        return match ($this->action) {
            'create' => ['label' => 'Created', 'class' => 'bg-green-100 text-green-800'],
            'update' => ['label' => 'Updated', 'class' => 'bg-blue-100 text-blue-800'],
            'delete' => ['label' => 'Deleted', 'class' => 'bg-red-100 text-red-800'],
            default   => ['label' => ucfirst($this->action), 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    /**
     * Short entity label — the class basename (e.g. "Goat").
     */
    public function getEntityLabelAttribute(): string
    {
        return class_basename($this->entity_type);
    }
}
