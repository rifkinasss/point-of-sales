<?php

namespace App\Actions\Owner;

use App\Enums\UserRole;
use App\Models\InvoiceSequence;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateStoreAction
{
    public function execute(User $owner, Store $store, array $attributes): Store
    {
        abort_unless($owner->role === UserRole::Owner && $owner->is_active, 403);
        abort_unless($owner->workspaceStores()->where('stores.public_id', $store->public_id)->exists(), 404);

        if (($attributes['store_code'] ?? $store->store_code) !== $store->store_code
            || ($attributes['branch_code'] ?? $store->branch_code) !== $store->branch_code) {
            $hasHistory = Sale::query()->withoutGlobalScopes()->where('store_id', $store->id)->exists()
                || InvoiceSequence::query()->where('store_id', $store->id)->exists();
            if ($hasHistory) {
                throw ValidationException::withMessages(['store_code' => 'Kode toko dan kode cabang tidak dapat diubah setelah transaksi dibuat.']);
            }
        }

        return DB::transaction(function () use ($store, $attributes): Store {
            $store->fill($attributes)->save();
            return $store->refresh();
        });
    }
}
