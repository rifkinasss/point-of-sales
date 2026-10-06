<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(string $event, Model $model, array $old = [], array $new = []): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $user = auth()->user();
        AuditLog::withoutEvents(function () use ($model, $user, $event, $old, $new): void {
            AuditLog::withoutGlobalScopes()->create([
                'store_id' => $model->getAttribute('store_id') ?? $user?->store_id,
                'user_id' => $user?->id,
                'event' => $event,
                'auditable_type' => $model::class,
                'auditable_id' => $model->getKey(),
                'old_values' => AuditLog::redactValues($old) ?: null,
                'new_values' => AuditLog::redactValues($new) ?: null,
                'url' => app()->runningInConsole() ? null : request()->fullUrl(),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            ]);
        });
    }
}
