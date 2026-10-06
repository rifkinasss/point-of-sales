<?php

namespace App\Actions\Owner;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;
use App\Policies\OwnerStaffPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStaffAction
{
    public function execute(User $owner, User $subject, array $attributes, array $storePublicIds): User
    {
        abort_unless(app(OwnerStaffPolicy::class)->view($owner, $subject), 404);
        $role = UserRole::from($attributes['role']);
        abort_unless(in_array($role, [UserRole::Manager, UserRole::Cashier], true), 403);
        $stores = app(ValidateStaffAssignmentsAction::class)->execute($owner, $role, $storePublicIds);
        $changed = $subject->role !== $role || $subject->is_active !== (bool) $attributes['is_active'];
        if ($role === UserRole::Cashier) $changed = $changed || (int) $subject->store_id !== (int) $stores[0]->id;
        else $changed = $changed || $subject->workspaceStores()->pluck('stores.id')->sort()->values()->all() !== collect($stores)->pluck('id')->sort()->values()->all();
        if ($changed && CashierShift::query()->where('user_id', $subject->id)->where('status', ShiftStatus::Open)->exists()) throw ValidationException::withMessages(['user' => 'Pengguna masih memiliki shift aktif. Tutup shift terlebih dahulu sebelum mengubah akses, role, atau status.']);
        return DB::transaction(function () use ($subject, $attributes, $role, $stores): User {
            $subject->forceFill(['name' => trim($attributes['name']), 'email' => strtolower(trim($attributes['email'])), 'role' => $role, 'is_active' => (bool) $attributes['is_active'], 'store_id' => $stores[0]->id])->save();
            if ($role === UserRole::Manager) $subject->workspaceStores()->sync(collect($stores)->mapWithKeys(fn ($store, int $index): array => [$store->id => ['is_default' => $index === 0]])->all());
            else $subject->workspaceStores()->detach();
            return $subject->refresh();
        });
    }
}
