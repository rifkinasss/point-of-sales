<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use BelongsToCurrentStore;

    protected $fillable = ['store_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'url', 'ip_address'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function store(): BelongsTo { return $this->belongsTo(Store::class); }

    public function resourceReference(): string
    {
        $model = $this->resolveAuditable();

        if (! $model) {
            return '#'.$this->auditable_id;
        }

        return match (true) {
            $model instanceof Sale => (string) ($model->invoice_number ?: $model->public_id ?: '#'.$model->getKey()),
            $model instanceof Store => (string) ($model->store_code ?: $model->public_id ?: '#'.$model->getKey()),
            $model instanceof Product => (string) ($model->sku ?: $model->public_id ?: $model->getKey()),
            $model instanceof User => (string) ($model->email ?: $model->name ?: $model->public_id ?: '#'.$model->getKey()),
            default => (string) ($model->public_id ?? '#'.$model->getKey()),
        };
    }

    /** @return array<string, mixed> */
    public function safeOldValues(): array
    {
        return self::redactValues($this->old_values ?? []);
    }

    /** @return array<string, mixed> */
    public function safeNewValues(): array
    {
        return self::redactValues($this->new_values ?? []);
    }

    /** @param array<string, mixed> $values */
    public static function redactValues(array $values): array
    {
        $sensitive = ['password', 'password_confirmation', 'remember_token', 'access_token', 'refresh_token', 'session', 'secret', 'credential'];

        return collect($values)->mapWithKeys(function (mixed $value, string|int $key) use ($sensitive): array {
            $keyString = Str::lower((string) $key);
            $isSensitive = collect($sensitive)->contains(fn (string $needle): bool => Str::contains($keyString, $needle));

            return [(string) $key => $isSensitive ? '[REDACTED]' : (is_array($value) ? self::redactValues($value) : $value)];
        })->all();
    }

    private function resolveAuditable(): ?Model
    {
        $allowed = [Sale::class, Store::class, Product::class, User::class, CashierShift::class, Payment::class, Purchase::class, Expense::class];

        if (! in_array($this->auditable_type, $allowed, true) || ! class_exists($this->auditable_type)) {
            return null;
        }

        $class = $this->auditable_type;

        return $class::withoutGlobalScopes()->find($this->auditable_id);
    }
}
