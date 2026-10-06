<?php

namespace App\Actions\Store;

use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetStoreStatusAction
{
    public function execute(User $actor, Store $store, StoreStatus $status): Store
    {
        $this->authorize($actor, $store);

        return DB::transaction(function () use ($store, $status): Store {
            $lockedStore = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();

            if ($status === StoreStatus::Inactive && CashierShift::withoutGlobalScopes()
                ->where('store_id', $lockedStore->id)
                ->where('status', ShiftStatus::Open)
                ->exists()) {
                throw ValidationException::withMessages([
                    'store' => 'Store memiliki shift kasir aktif. Tutup semua shift terlebih dahulu sebelum menonaktifkan store.',
                ]);
            }

            $oldStatus = $lockedStore->status?->value;
            $lockedStore->update(['status' => $status]);
            app(AuditLogger::class)->log('store_status_changed', $lockedStore, ['status' => $oldStatus], ['status' => $status->value]);

            return $lockedStore->refresh();
        });
    }

    private function authorize(User $actor, Store $store): void
    {
        abort_unless($actor->is_active && in_array($actor->role, [UserRole::Admin, UserRole::Owner], true), 403);

        if ($actor->role === UserRole::Owner) {
            abort_unless($actor->workspaceStores()->where('stores.public_id', $store->public_id)->exists(), 404);
        }
    }
}
