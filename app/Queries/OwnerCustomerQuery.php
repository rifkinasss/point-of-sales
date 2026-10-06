<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OwnerCustomerQuery
{
    public function paginate(User $owner, array $filters): LengthAwarePaginator
    {
        $query = $this->base($owner, $filters)
            ->with(['store'])
            ->withCount(['sales as total_transactions' => fn ($sales) => $sales->where('status', SaleStatus::Paid)])
            ->withSum(['sales as total_spend' => fn ($sales) => $sales->where('status', SaleStatus::Paid)], 'grand_total')
            ->addSelect(['last_transaction_at' => Sale::query()->withoutGlobalScopes()->select('completed_at')->whereColumn('customer_id', 'customers.id')->where('status', SaleStatus::Paid)->latest('completed_at')->limit(1)]);

        return $query->orderBy('name')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $owner, string $publicId): ?Customer
    {
        return Customer::query()->withoutGlobalScopes()->with('store')->whereIn('store_id', $this->storeIds($owner))->where('public_id', $publicId)->first();
    }

    public function transactions(User $owner, Customer $customer): LengthAwarePaginator
    {
        return Sale::query()->withoutGlobalScopes()->with(['cashier', 'store', 'payments'])->whereIn('store_id', $this->storeIds($owner))->where('customer_id', $customer->id)->whereIn('status', [SaleStatus::Paid, SaleStatus::Refunded, SaleStatus::Voided])->latest('completed_at')->paginate(10, pageName: 'customerTransactionsPage');
    }

    public function metrics(User $owner): array
    {
        $ids = $this->storeIds($owner); $paid = Sale::query()->withoutGlobalScopes()->whereIn('store_id', $ids)->where('status', SaleStatus::Paid); $spend = (int) (clone $paid)->sum('grand_total'); $count = (int) (clone $paid)->count();
        return ['total' => Customer::query()->withoutGlobalScopes()->whereIn('store_id', $ids)->count(), 'active' => Customer::query()->withoutGlobalScopes()->whereIn('store_id', $ids)->where('is_active', true)->count(), 'transactions' => $count, 'spend' => $spend, 'average' => $count ? intdiv($spend, $count) : 0];
    }

    public function customerMetrics(User $owner, Customer $customer): array
    {
        $sales = Sale::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->where('customer_id', $customer->id)->where('status', SaleStatus::Paid); $count = (int) (clone $sales)->count(); $spend = (int) (clone $sales)->sum('grand_total'); $first = (clone $sales)->min('completed_at'); $last = (clone $sales)->max('completed_at');
        return ['transactions' => $count, 'spend' => $spend, 'average' => $count ? intdiv($spend, $count) : 0, 'first' => $first ? CarbonImmutable::parse($first, 'UTC') : null, 'last' => $last ? CarbonImmutable::parse($last, 'UTC') : null];
    }

    /** @return array<int, int> */
    public function storeIds(User $owner): array { return app(ActiveStoreContext::class)->authorizedStores($owner)->pluck('id')->all(); }

    private function base(User $owner, array $filters): Builder
    {
        $query = Customer::query()->withoutGlobalScopes()->whereIn('customers.store_id', $this->storeIds($owner));
        if (filled($filters['store'] ?? null)) { $store = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $filters['store']); $query->where('store_id', $store?->id ?: 0); }
        if (filled($filters['search'] ?? null)) { $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%'; $query->where(fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$needle])->orWhereRaw("LOWER(COALESCE(phone, '')) LIKE ?", [$needle])->orWhereRaw("LOWER(COALESCE(email, '')) LIKE ?", [$needle])->orWhereRaw('LOWER(code) LIKE ?', [$needle])); }
        if (($filters['active'] ?? '') !== '') $query->where('is_active', (bool) ((string) $filters['active'] === '1'));
        return $query;
    }
}
