<?php

namespace App\Actions\Admin;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageUserAccessAction
{
    public function create(User $admin, array $attributes, array $storePublicIds = []): User
    {
        $this->authorizeAdmin($admin);
        $role = $this->resolveRole($attributes['role'] ?? null);
        $this->assertAssignableRole($role);
        $this->validateAttributes($attributes);
        $stores = $this->resolveStores($role, $storePublicIds);

        return DB::transaction(function () use ($admin, $attributes, $role, $stores): User {
            $user = User::query()->create([
                'name' => trim((string) ($attributes['name'] ?? '')),
                'email' => strtolower(trim((string) ($attributes['email'] ?? ''))),
                'password' => filled($attributes['password'] ?? null)
                    ? Hash::make((string) $attributes['password'])
                    : Hash::make(Str::random(64)),
                'role' => $role,
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'store_id' => $stores[0]->id ?? null,
            ]);

            $this->syncManagerStores($user, $role, $stores);
            app(AuditLogger::class)->log('user_access_created', $user, [], $this->safeAccessData($user, $stores));

            return $user->refresh();
        });
    }

    public function update(User $admin, User $subject, array $attributes, array $storePublicIds = []): User
    {
        $this->authorizeAdmin($admin);
        $role = $this->resolveRole($attributes['role'] ?? null);
        $this->assertAssignableRole($role, $subject);
        $this->validateAttributes($attributes, $subject);
        $stores = $this->resolveStores($role, $storePublicIds, $subject);
        $isActive = (bool) ($attributes['is_active'] ?? $subject->is_active);

        $this->protectSelf($admin, $subject, $role, $isActive);
        $this->protectLastAdmin($subject, $role, $isActive);
        $this->protectActiveShift($subject, $role, $isActive, $stores);

        $old = $this->safeAccessData($subject, $this->currentStores($subject));

        return DB::transaction(function () use ($admin, $subject, $attributes, $role, $stores, $isActive, $old): User {
            $data = [
                'name' => trim((string) ($attributes['name'] ?? $subject->name)),
                'email' => strtolower(trim((string) ($attributes['email'] ?? $subject->email))),
                'role' => $role,
                'is_active' => $isActive,
                'store_id' => $stores[0]->id ?? null,
            ];

            if (filled($attributes['password'] ?? null)) {
                $data['password'] = Hash::make((string) $attributes['password']);
                $data['remember_token'] = null;
            }

            $subject->forceFill($data)->save();
            $this->syncManagerStores($subject, $role, $stores);
            app(AuditLogger::class)->log('user_access_updated', $subject, $old, $this->safeAccessData($subject, $stores));

            return $subject->refresh();
        });
    }

    public function sendPasswordReset(User $admin, User $subject): string
    {
        $this->authorizeAdmin($admin);
        $status = Password::sendResetLink(['email' => $subject->email]);
        app(AuditLogger::class)->log('user_password_reset_requested', $subject, [], ['email' => $subject->email]);

        return $status;
    }

    /** @return array<int, Store> */
    public function currentStores(User $subject): array
    {
        if ($subject->role === UserRole::Manager) {
            return $subject->managedStores()->get()->all();
        }

        return $subject->store ? [$subject->store] : [];
    }

    private function authorizeAdmin(User $admin): void
    {
        abort_unless($admin->is_active && $admin->role === UserRole::Admin, 403);
    }

    private function validateAttributes(array $attributes, ?User $subject = null): void
    {
        Validator::make($attributes, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($subject?->id)],
            'is_active' => ['boolean'],
        ])->validate();
    }

    private function resolveRole(mixed $value): UserRole
    {
        try {
            return $value instanceof UserRole ? $value : UserRole::from((string) $value);
        } catch (\ValueError) {
            throw ValidationException::withMessages(['data.role' => 'Role pengguna tidak valid.']);
        }
    }

    private function assertAssignableRole(UserRole $role, ?User $subject = null): void
    {
        if ($subject?->role === UserRole::Admin && $role === UserRole::Admin) {
            return;
        }

        if (! array_key_exists($role->value, UserRole::assignableOptionsFor(UserRole::Admin))) {
            throw ValidationException::withMessages(['data.role' => 'Role tersebut tidak dapat ditetapkan oleh Admin.']);
        }
    }

    /** @return array<int, Store> */
    private function resolveStores(UserRole $role, array $storePublicIds, ?User $subject = null): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $storePublicIds))));

        if ($role === UserRole::Admin) {
            if ($ids !== []) {
                throw ValidationException::withMessages(['data.store_public_ids' => 'Admin tidak memerlukan assignment toko.']);
            }

            return [];
        }

        $stores = Store::query()->withoutGlobalScopes()->whereIn('public_id', $ids)->get();
        if (count($ids) !== $stores->count()) {
            throw ValidationException::withMessages(['data.store_public_ids' => 'Store assignment tidak valid.']);
        }

        $currentIds = collect($this->currentStores($subject ?? new User))->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $hasNewInactiveStore = $stores->contains(fn (Store $store): bool => ! $store->isActive() && ! in_array((string) $store->id, $currentIds, true));
        if ($hasNewInactiveStore || $stores->contains(fn (Store $store): bool => $store->status === null)) {
            throw ValidationException::withMessages(['data.store_public_ids' => 'Store tidak aktif tidak dapat menjadi assignment baru.']);
        }

        if ($role === UserRole::Manager && $stores->isEmpty()) {
            throw ValidationException::withMessages(['data.store_public_ids' => 'Manager harus memiliki minimal satu store.']);
        }

        if (in_array($role, [UserRole::Owner, UserRole::Cashier], true) && $stores->count() !== 1) {
            throw ValidationException::withMessages(['data.store_public_ids' => 'Role ini harus memiliki tepat satu store.']);
        }

        return $stores->all();
    }

    /** @param array<int, Store> $stores */
    private function syncManagerStores(User $user, UserRole $role, array $stores): void
    {
        if ($role === UserRole::Manager) {
            $user->workspaceStores()->sync(collect($stores)->mapWithKeys(fn (Store $store, int $index): array => [
                $store->id => ['is_default' => $index === 0],
            ])->all());
        } else {
            $user->workspaceStores()->detach();
        }
    }

    private function protectSelf(User $admin, User $subject, UserRole $role, bool $isActive): void
    {
        if ($admin->is($subject) && ($role !== $subject->role || ! $isActive)) {
            throw ValidationException::withMessages(['data.role' => 'Admin tidak dapat menonaktifkan atau mengganti role akun sendiri.']);
        }
    }

    private function protectLastAdmin(User $subject, UserRole $role, bool $isActive): void
    {
        if ($subject->role === UserRole::Admin && ($role !== UserRole::Admin || ! $isActive)
            && User::query()->where('role', UserRole::Admin)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['data.is_active' => 'Minimal satu Admin aktif harus tetap tersedia.']);
        }
    }

    /** @param array<int, Store> $stores */
    private function protectActiveShift(User $subject, UserRole $role, bool $isActive, array $stores): void
    {
        if (! CashierShift::query()->where('user_id', $subject->id)->where('status', ShiftStatus::Open)->exists()) {
            return;
        }

        $currentStoreIds = collect($this->currentStores($subject))->pluck('id')->sort()->values()->all();
        $newStoreIds = collect($stores)->pluck('id')->sort()->values()->all();
        if ($subject->role !== $role || $subject->is_active !== $isActive || $currentStoreIds !== $newStoreIds) {
            throw ValidationException::withMessages(['data.role' => 'Akun dengan shift aktif tidak dapat diubah role, status, atau assignment-nya.']);
        }
    }

    /** @param array<int, Store> $stores */
    private function safeAccessData(User $user, array $stores): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'is_active' => (bool) $user->is_active,
            'store_public_ids' => collect($stores)->pluck('public_id')->values()->all(),
        ];
    }
}
