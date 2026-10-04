<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManagerCustomerQuery
{
    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        $query = $this->base($manager, $filters)
            ->withCount(['sales as total_transactions' => fn ($sales) => $sales->where('status', SaleStatus::Paid)])
            ->withSum(['sales as total_spend' => fn ($sales) => $sales->where('status', SaleStatus::Paid)], 'grand_total')
            ->addSelect(['last_transaction_at' => Sale::query()->withoutGlobalScopes()->select('completed_at')->whereColumn('customer_id', 'customers.id')->where('status', SaleStatus::Paid)->latest('completed_at')->limit(1)]);

        return $query->orderBy('name')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $manager, string $publicId): ?Customer
    {
        return Customer::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('public_id', $publicId)->first();
    }

    public function transactions(User $manager, Customer $customer): LengthAwarePaginator
    {
        return Sale::query()->withoutGlobalScopes()->with(['cashier', 'payments'])
            ->where('store_id', $manager->store_id)->where('customer_id', $customer->id)
            ->whereIn('status', [SaleStatus::Paid, SaleStatus::Refunded, SaleStatus::Voided])
            ->latest('completed_at')->latest('created_at')->paginate(10, pageName: 'customerTransactionsPage');
    }

    /** @return array{total:int,activeThisMonth:int,newThisMonth:int,returning:int,averageSpend:int} */
    public function metrics(User $manager): array
    {
        $timezone = $manager->store?->timezone ?: config('app.timezone', 'UTC');
        $month = CarbonImmutable::now($timezone);
        $paidSales = Sale::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid);
        $spend = (int) (clone $paidSales)->sum('grand_total');
        $transactions = (int) (clone $paidSales)->count();

        return [
            'total' => Customer::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->count(),
            'activeThisMonth' => Customer::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->whereHas('sales', fn ($sales) => $sales->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid)->whereBetween('completed_at', [$month->startOfMonth()->utc(), $month->endOfMonth()->utc()]))->count(),
            'newThisMonth' => Customer::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->whereBetween('created_at', [$month->startOfMonth()->utc(), $month->endOfMonth()->utc()])->count(),
            'returning' => Customer::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->whereIn('id', (clone $paidSales)->select('customer_id')->whereNotNull('customer_id')->groupBy('customer_id')->havingRaw('COUNT(*) >= 2'))->count(),
            'averageSpend' => $transactions > 0 ? intdiv($spend, $transactions) : 0,
        ];
    }

    /** @return array{transactions:int,spend:int,average:int,first:?string,last:?string} */
    public function customerMetrics(User $manager, Customer $customer): array
    {
        $sales = Sale::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('customer_id', $customer->id)->where('status', SaleStatus::Paid);
        $count = (int) (clone $sales)->count();
        $spend = (int) (clone $sales)->sum('grand_total');

        return [
            'transactions' => $count,
            'spend' => $spend,
            'average' => $count > 0 ? intdiv($spend, $count) : 0,
            'first' => ($first = (clone $sales)->min('completed_at')) ? CarbonImmutable::parse($first, 'UTC') : null,
            'last' => ($last = (clone $sales)->max('completed_at')) ? CarbonImmutable::parse($last, 'UTC') : null,
        ];
    }

    private function base(User $manager, array $filters): Builder
    {
        $query = Customer::query()->withoutGlobalScopes()->where('customers.store_id', $manager->store_id);
        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->where(fn (Builder $q) => $q->whereRaw('LOWER(customers.name) LIKE ?', [$needle])->orWhereRaw('LOWER(COALESCE(customers.phone, \'\')) LIKE ?', [$needle])->orWhereRaw('LOWER(COALESCE(customers.email, \'\')) LIKE ?', [$needle])->orWhereRaw('LOWER(customers.code) LIKE ?', [$needle]));
        }
        if (($filters['active'] ?? '') !== '') {
            $query->where('is_active', (bool) ((string) $filters['active'] === '1'));
        }
        if (($filters['activity'] ?? '') === 'transacted') {
            $query->whereHas('sales', fn ($sales) => $sales->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid));
        } elseif (($filters['activity'] ?? '') === 'never') {
            $query->whereDoesntHave('sales', fn ($sales) => $sales->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid));
        } elseif (($filters['activity'] ?? '') === 'last_30') {
            $since = CarbonImmutable::now($manager->store?->timezone ?: config('app.timezone', 'UTC'))->subDays(30)->startOfDay()->utc();
            $query->whereHas('sales', fn ($sales) => $sales->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid)->where('completed_at', '>=', $since));
        }

        return $query;
    }
}
