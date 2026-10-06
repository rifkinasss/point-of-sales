<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreScope;
use Illuminate\Support\Collection;

class ActiveStoreContext
{
    private const MANAGER_SESSION_KEY = 'manager.active_store_public_id';
    private const OWNER_SESSION_KEY = 'owner.active_store_public_id';

    public function availableStores(User $user): Collection
    {
        return $this->authorizedStores($user)
            ->filter(fn (Store $store): bool => $store->isActive())
            ->values();
    }

    public function authorizedStores(User $user): Collection
    {
        if (! in_array($user->role, [UserRole::Manager, UserRole::Owner], true)) {
            return $user->store_id ? Store::query()->whereKey($user->store_id)->get() : collect();
        }

        $stores = $user->workspaceStores()
            ->orderBy('name')
            ->get();

        if ($stores->isEmpty() && $user->store_id) {
            return Store::query()->whereKey($user->store_id)->get();
        }

        return $stores;
    }

    public function scope(User $user): StoreScope
    {
        $stores = $this->availableStores($user);

        if (! in_array($user->role, [UserRole::Manager, UserRole::Owner], true)) {
            return new StoreScope($stores);
        }

        $selected = (string) session($this->sessionKey($user), '');
        if ($selected === 'all' && $stores->count() > 1) {
            return new StoreScope($stores, true);
        }
        if ($selected === '' && $user->role === UserRole::Owner && $stores->count() > 1) {
            session()->put($this->sessionKey($user), 'all');
            return new StoreScope($stores, true);
        }

        $store = $selected !== ''
            ? $stores->firstWhere('public_id', $selected)
            : $this->defaultStore($user, $stores);

        if (! $store && $user->role === UserRole::Owner && $stores->count() > 1) {
            session()->put($this->sessionKey($user), 'all');
            return new StoreScope($stores, true);
        }

        if (! $store) {
            $store = $this->defaultStore($user, $stores);
        }

        if ($store) {
            session()->put($this->sessionKey($user), $store->public_id);
            return new StoreScope(collect([$store]));
        }

        session()->forget($this->sessionKey($user));
        return new StoreScope(collect());
    }

    public function switch(User $user, string $identifier): void
    {
        abort_unless(in_array($user->role, [UserRole::Manager, UserRole::Owner], true) && $user->is_active, 403);
        $stores = $this->availableStores($user);

        if ($identifier === 'all') {
            abort_unless($stores->count() > 1, 403);
            session()->put($this->sessionKey($user), 'all');
            return;
        }

        abort_unless($stores->contains('public_id', $identifier), 403);
        session()->put($this->sessionKey($user), $identifier);
    }

    public function identifier(User $user): string
    {
        $scope = $this->scope($user);
        return $scope->all ? 'all' : (string) $scope->selected()?->public_id;
    }

    public function current(User $user): ?Store
    {
        return $this->scope($user)->selected();
    }

    public function default(User $user): ?Store
    {
        return $this->defaultStore($user, $this->availableStores($user));
    }

    public function requireSingleStore(User $user): Store
    {
        $scope = $this->scope($user);
        abort_unless($scope->requiresSingleStore(), 422, 'Pilih satu cabang sebelum melakukan perubahan.');
        return $scope->selected();
    }

    private function defaultStore(User $user, Collection $stores): ?Store
    {
        return $stores->first(fn (Store $store): bool => $store->pivot?->is_default === true)
            ?? $stores->firstWhere('id', $user->store_id)
            ?? $stores->first();
    }

    private function sessionKey(User $user): string
    {
        return $user->role === UserRole::Owner
            ? self::OWNER_SESSION_KEY
            : self::MANAGER_SESSION_KEY;
    }
}
