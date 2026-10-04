<?php

namespace App\Actions\Sale;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidSaleAction
{
    public function handle(Sale $sale, User $user, string $reason): Sale
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true)) {
            throw ValidationException::withMessages(['authorization' => 'Role ini tidak berwenang melakukan void transaksi.']);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Alasan void wajib diisi.']);
        }

        return DB::transaction(function () use ($reason, $sale, $user): Sale {
            $sale = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($user->is_active === false || ($sale->store_id && $user->store_id && $sale->store_id !== $user->store_id)) {
                throw ValidationException::withMessages(['sale' => 'Transaksi berasal dari toko lain.']);
            }

            $sale->transitionTo(SaleStatus::Voided)->save();
            app(AuditLogger::class)->log('sale_voided', $sale, [], ['reason' => $reason, 'actor_id' => $user->id]);

            return $sale->refresh();
        });
    }
}
