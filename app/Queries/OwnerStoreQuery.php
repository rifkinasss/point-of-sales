<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StoreStatus;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Collection;

class OwnerStoreQuery
{
    public function authorizedStores(User $owner): Collection
    {
        return app(ActiveStoreContext::class)->authorizedStores($owner)->sortBy('name')->values();
    }

    public function findAuthorized(User $owner, string $publicId): Store
    {
        return $this->authorizedStores($owner)->firstWhere('public_id', $publicId) ?? abort(404);
    }

    public function filtered(User $owner, string $search = '', string $status = ''): Collection
    {
        return $this->authorizedStores($owner)
            ->when(filled($search), fn (Collection $stores): Collection => $stores->filter(function (Store $store) use ($search): bool {
                $needle = mb_strtolower(trim($search));
                return str_contains(mb_strtolower((string) $store->name), $needle)
                    || str_contains(mb_strtolower((string) $store->store_code), $needle)
                    || str_contains(mb_strtolower((string) $store->branch_code), $needle)
                    || str_contains(mb_strtolower((string) $store->branch_name), $needle);
            }))
            ->when(filled($status), fn (Collection $stores): Collection => $stores->filter(fn (Store $store): bool => $store->status?->value === $status))
            ->values();
    }

    /** @return array<string, int> */
    public function summary(Store $store): array
    {
        $sales = Sale::query()->withoutGlobalScopes()->where('store_id', $store->id);
        $paidSales = (clone $sales)->where('status', SaleStatus::Paid);
        $refunds = Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Refunded)
            ->whereHas('sale', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $store->id));

        return [
            'transactions' => (clone $paidSales)->count(),
            'netSales' => max(0, (int) (clone $paidSales)->sum('grand_total') - (int) $refunds->sum('amount_due')),
            'activeShifts' => CashierShift::query()->where('store_id', $store->id)->whereNull('closed_at')->count(),
            'activeProducts' => $store->products()->where('is_active', true)->count(),
        ];
    }

    /** @return array<string, string> */
    public function managers(Store $store): array
    {
        return $store->managers()->get()->filter(fn (User $user): bool => $user->role?->value === 'manager')
            ->mapWithKeys(fn (User $user): array => [$user->public_id ?: (string) $user->id => $user->name])->all();
    }
}
