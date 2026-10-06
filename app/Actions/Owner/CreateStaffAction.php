<?php

namespace App\Actions\Owner;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\OwnerStaffPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStaffAction
{
    public function execute(User $owner, array $attributes, array $storePublicIds): User
    {
        abort_unless(app(OwnerStaffPolicy::class)->manage($owner), 403);
        $role = UserRole::from($attributes['role']);
        abort_unless(in_array($role, [UserRole::Manager, UserRole::Cashier], true), 403);
        $stores = app(ValidateStaffAssignmentsAction::class)->execute($owner, $role, $storePublicIds);
        return DB::transaction(function () use ($attributes, $role, $stores): User {
            $user = User::create([
                'name' => trim($attributes['name']), 'email' => strtolower(trim($attributes['email'])),
                'role' => $role, 'is_active' => (bool) ($attributes['is_active'] ?? true),
                'store_id' => $stores[0]->id, 'password' => Hash::make(Str::random(64)),
            ]);
            if ($role === UserRole::Manager) $user->workspaceStores()->sync(collect($stores)->mapWithKeys(fn ($store, int $index): array => [$store->id => ['is_default' => $index === 0]])->all());
            return $user->refresh();
        });
    }
}
