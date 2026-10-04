<?php

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCurrentStore
{
    public static function bootBelongsToCurrentStore(): void
    {
        static::addGlobalScope('current-store', function (Builder $query): void {
            $user = auth()->user();

            if ($user instanceof User && $user->role !== UserRole::Admin && $user->store_id) {
                $query->where($query->getModel()->getTable().'.store_id', $user->store_id);
            }
        });

        static::creating(function (self $model): void {
            $user = auth()->user();

            if (! $model->store_id && $user instanceof User && $user->store_id) {
                $model->store_id = $user->store_id;
            }
        });
    }
}
