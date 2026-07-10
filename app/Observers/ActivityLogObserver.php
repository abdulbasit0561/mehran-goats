<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogObserver
{
    /**
     * Fields never to record in activity logs (sensitive / noise).
     */
    private const HIDDEN_FIELDS = [
        'password',
        'password_hash',
        'remember_token',
        'email_verified_at',
        'updated_at',
    ];

    // ─── Eloquent event handlers ────────────────────────────────────────

    public function created(Model $model): void
    {
        $this->record('create', $model, null, $this->cleanAttributes($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        // Skip if only timestamps changed
        $meaningful = array_diff_key($changes, array_flip(['updated_at']));
        if (empty($meaningful)) {
            return;
        }

        $old = [];
        foreach ($meaningful as $field => $newValue) {
            $old[$field] = $model->getOriginal($field);
        }

        $this->record('update', $model, $this->cleanArray($old), $this->cleanArray($meaningful));
    }

    public function deleted(Model $model): void
    {
        $this->record('delete', $model, $this->cleanAttributes($model->getAttributes()), null);
    }

    // ─── Core logging method ────────────────────────────────────────────

    private function record(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'user_id'     => $user?->id,
            'user_name'   => $user?->name ?? 'System',
            'action'      => $action,
            'entity_type' => $model::class,
            'entity_id'   => $model->getKey(),
            'description' => $this->buildDescription($action, $model),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function buildDescription(string $action, Model $model): string
    {
        $label = class_basename($model);

        // Try to identify the record by a common descriptor
        $identifier = match (true) {
            isset($model->tag_id)   => $model->tag_id,
            isset($model->name)     => $model->name,
            isset($model->category) => $model->category,
            isset($model->type)     => $model->type,
            default                 => "#{$model->getKey()}",
        };

        $verb = match ($action) {
            'create' => 'created',
            'update' => 'updated',
            'delete' => 'deleted',
            default  => $action,
        };

        return "{$label} '{$identifier}' {$verb}";
    }

    /**
     * Remove sensitive fields from a raw attributes array.
     */
    private function cleanAttributes(array $attributes): array
    {
        return $this->cleanArray($attributes);
    }

    private function cleanArray(array $data): array
    {
        return array_filter(
            $data,
            fn ($key) => !in_array($key, self::HIDDEN_FIELDS, true),
            ARRAY_FILTER_USE_KEY
        );
    }
}
