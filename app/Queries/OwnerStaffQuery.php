<?php

namespace App\Queries;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OwnerStaffQuery
{
    public function authorizedStores(User $owner, bool $activeOnly = false): Collection
    {
        $stores = app(ActiveStoreContext::class)->authorizedStores($owner);
        return $activeOnly ? $stores->filter(fn (Store $store): bool => $store->isActive())->values() : $stores;
    }

    public function users(User $owner, array $filters = []): Builder
    {
        $storeIds = $this->authorizedStores($owner)->pluck('id')->all();
        $query = User::query()
            ->whereIn('role', [UserRole::Manager, UserRole::Cashier])
            ->where(function (Builder $users) use ($storeIds): void {
                $users->whereIn('store_id', $storeIds)
                    ->orWhereHas('workspaceStores', fn (Builder $stores) => $stores->whereIn('stores.id', $storeIds));
            })
            ->with(['workspaceStores:id,public_id,name,branch_name,branch_code,status', 'store:id,public_id,name,branch_name,branch_code,status']);

        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->where(fn (Builder $users) => $users
                ->whereRaw('LOWER(name) LIKE ?', [$needle])
                ->orWhereRaw('LOWER(email) LIKE ?', [$needle]));
        }
        if (filled($filters['role'] ?? null)) {
            $query->where('role', $filters['role']);
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('is_active', $filters['status'] === 'active');
        }
        if (filled($filters['store'] ?? null)) {
            $storeId = $this->authorizedStores($owner)->firstWhere('public_id', $filters['store'])?->id;
            $query->where(function (Builder $users) use ($storeId): void {
                if ($storeId) {
                    $users->where('store_id', $storeId)->orWhereHas('workspaceStores', fn (Builder $stores) => $stores->whereKey($storeId));
                } else {
                    $users->whereRaw('1 = 0');
                }
            });
        }

        return $query->orderBy('name');
    }

    public function findAuthorized(User $owner, string $publicId): User
    {
        return $this->users($owner)->where('public_id', $publicId)->first() ?? abort(404);
    }

    public function isAuthorizedSubject(User $owner, User $subject): bool
    {
        return $this->users($owner)->whereKey($subject->id)->exists();
    }

    public function isAuthorizedStore(User $owner, Store $store): bool
    {
        return $this->authorizedStores($owner)->contains('id', $store->id);
    }
}
