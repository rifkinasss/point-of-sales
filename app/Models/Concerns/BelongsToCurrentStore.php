<?php

namespace App\Models\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCurrentStore
{
    public static function bootBelongsToCurrentStore(): void
    {
        static::addGlobalScope('current-store', function (Builder $query): void {
            $user = auth()->user();

            if ($user instanceof User
                && $user->role !== UserRole::Admin
                && ($user->store_id || in_array($user->role, [UserRole::Owner, UserRole::Manager], true))) {
                $scope = app(ActiveStoreContext::class)->scope($user);
                $query->whereIn($query->getModel()->getTable().'.store_id', $scope->ids());
            }
        });

        static::creating(function (self $model): void {
            $user = auth()->user();

            if (! $model->store_id && $user instanceof User && $user->role !== UserRole::Admin) {
                $model->store_id = app(ActiveStoreContext::class)->requireSingleStore($user)->getKey();
            }
        });
    }
}
