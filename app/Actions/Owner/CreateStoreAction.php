<?php

namespace App\Actions\Owner;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateStoreAction
{
    public function execute(User $owner, array $attributes): Store
    {
        abort_unless($owner->role === UserRole::Owner && $owner->is_active, 403);

        return DB::transaction(function () use ($owner, $attributes): Store {
            $store = Store::create($attributes + ['status' => 'active']);
            $hasDefault = $owner->workspaceStores()->exists();
            $owner->workspaceStores()->attach($store->id, ['is_default' => ! $hasDefault]);

            return $store;
        });
    }
}
