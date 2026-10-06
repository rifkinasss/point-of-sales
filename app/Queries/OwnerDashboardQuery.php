<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OwnerDashboardQuery
{
    /** @return array<string, mixed> */
    public function dashboard(User $owner): array
    {
        $scope = app(ActiveStoreContext::class)->scope($owner);
        $stores = $scope->stores;
        $ranges = $this->businessDateRanges($stores);
        $storeIds = $stores->pluck('id')->map(fn ($id): int => (int) $id)->all();

        $paidSales = $this->paidSales($storeIds, $ranges);
        $sales = (clone $paidSales)->get([
            'id', 'store_id', 'invoice_number', 'cashier_id', 'customer_id',
            'subtotal', 'item_discount_total', 'promotion_discount_total',
            'transaction_discount_total', 'grand_total', 'completed_at', 'created_at',
        ]);
        $refunds = $this->refundPayments($storeIds, $ranges)->get(['amount_due', 'refunded_at', 'sale_id']);
        $payments = $this->completedPayments($storeIds, $ranges)->get(['method', 'amount_due', 'sale_id']);

        $transactionCount = $sales->count();
        $grossSales = (int) $sales->sum('subtotal');
        $discount = (int) $sales->sum('item_discount_total')
            + (int) $sales->sum('promotion_discount_total')
            + (int) $sales->sum('transaction_discount_total');
        $refundTotal = (int) $refunds->sum('amount_due');
        $netSales = max(0, (int) $sales->sum('grand_total') - $refundTotal);
        $paymentBreakdown = $payments->groupBy(fn (Payment $payment): string => $payment->method->value)
            ->map(fn (Collection $rows): int => (int) $rows->sum('amount_due'))->all();

        return [
            'context' => ['all' => $scope->all, 'storeCount' => $stores->count(), 'store' => $scope->selected()],
            'metrics' => [
                'netSales' => $netSales,
                'transactions' => $transactionCount,
                'averageTransaction' => $transactionCount > 0 ? intdiv($netSales, $transactionCount) : 0,
                'refunds' => $refundTotal,
                'cashSales' => (int) ($paymentBreakdown['cash'] ?? 0),
                'activeStores' => $stores->count(),
            ],
            'salesPerformance' => ['gross' => $grossSales, 'discount' => $discount, 'refunds' => $refundTotal, 'net' => $netSales],
            'storePerformance' => $this->storePerformance($stores, $sales, $refunds, $payments),
            'paymentBreakdown' => $paymentBreakdown,
            'alerts' => $this->alerts($storeIds, $ranges),
            'recentSales' => $this->recentSales($storeIds, $ranges),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function storePerformance(Collection $stores, Collection $sales, Collection $refunds, Collection $payments): Collection
    {
        return $stores->map(function (Store $store) use ($sales, $refunds, $payments): array {
            $storeSales = $sales->where('store_id', $store->id);
            $storeRefunds = $refunds->whereIn('sale_id', $storeSales->pluck('id'));
            $storePayments = $payments->whereIn('sale_id', $storeSales->pluck('id'));

            return [
                'store' => $store,
                'transactions' => $storeSales->count(),
                'netSales' => max(0, (int) $storeSales->sum('grand_total') - (int) $storeRefunds->sum('amount_due')),
                'refunds' => (int) $storeRefunds->sum('amount_due'),
                'cashSales' => (int) $storePayments
                    ->filter(fn (Payment $payment): bool => $payment->method->value === 'cash')
                    ->sum('amount_due'),
            ];
        })->sortByDesc('netSales')->values();
    }

    /** @return array<int, array{store_id: int, start: CarbonImmutable, end: CarbonImmutable}> */
    private function businessDateRanges(Collection $stores): array
    {
        return $stores->map(fn (Store $store): array => [
            'store_id' => (int) $store->id,
            'start' => CarbonImmutable::now($store->timezone ?: config('app.timezone', 'UTC'))->startOfDay()->utc(),
            'end' => CarbonImmutable::now($store->timezone ?: config('app.timezone', 'UTC'))->endOfDay()->utc(),
        ])->values()->all();
    }

    private function paidSales(array $storeIds, array $ranges): Builder
    {
        return $this->applyRanges(
            Sale::query()->withoutGlobalScopes()->where('status', SaleStatus::Paid)->whereIn('store_id', $storeIds),
            $ranges,
            'completed_at',
        );
    }

    private function refundPayments(array $storeIds, array $ranges): Builder
    {
        return $this->applyPaymentRanges(
            Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Refunded),
            $ranges,
            'refunded_at',
            $storeIds,
        );
    }

    private function completedPayments(array $storeIds, array $ranges): Builder
    {
        return $this->applyPaymentRanges(
            Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Completed),
            $ranges,
            'paid_at',
            $storeIds,
        );
    }

    private function applyRanges(Builder $query, array $ranges, string $column): Builder
    {
        return $query->where(function (Builder $outer) use ($ranges, $column): void {
            foreach ($ranges as $range) {
                $outer->orWhere(fn (Builder $inner) => $inner
                    ->where('store_id', $range['store_id'])
                    ->whereBetween($column, [$range['start'], $range['end']]));
            }
        });
    }

    private function applyPaymentRanges(Builder $query, array $ranges, string $column, array $storeIds): Builder
    {
        return $query->whereHas('sale', fn (Builder $sale) => $sale->withoutGlobalScopes()->whereIn('sales.store_id', $storeIds))
            ->where(function (Builder $outer) use ($ranges, $column): void {
                foreach ($ranges as $range) {
                    $outer->orWhere(function (Builder $inner) use ($range, $column): void {
                        $inner->whereBetween($column, [$range['start'], $range['end']])
                            ->whereHas('sale', fn (Builder $sale) => $sale->withoutGlobalScopes()->where('sales.store_id', $range['store_id']));
                    });
                }
            });
    }

    /** @return array<int, array{type: string, label: string}> */
    private function alerts(array $storeIds, array $ranges): array
    {
        $alerts = [];
        $activeShifts = $this->applyRanges(
            CashierShift::query()->withoutGlobalScopes()->where('status', 'open')->whereIn('store_id', $storeIds),
            $ranges,
            'opened_at',
        )->count();
        $lowStock = Product::query()->withoutGlobalScopes()->whereIn('store_id', $storeIds)
            ->where('is_active', true)
            ->whereHas('inventory', fn (Builder $query) => $query->whereColumn('quantity', '<=', 'products.minimum_stock'))
            ->count();

        if ($activeShifts === 0) {
            $alerts[] = ['type' => 'warning', 'label' => 'Tidak ada shift aktif pada konteks ini.'];
        }
        if ($lowStock > 0) {
            $alerts[] = ['type' => 'warning', 'label' => $lowStock.' produk berada pada atau di bawah stok minimum.'];
        }

        $refundCount = $this->refundPayments($storeIds, $ranges)->count();
        if ($refundCount > 0) {
            $alerts[] = ['type' => 'info', 'label' => $refundCount.' refund tercatat hari ini.'];
        }

        return $alerts;
    }

    private function recentSales(array $storeIds, array $ranges): Collection
    {
        return $this->applyRanges(
            Sale::query()->withoutGlobalScopes()->with(['store', 'cashier', 'payments'])
                ->where('status', SaleStatus::Paid)->whereIn('store_id', $storeIds),
            $ranges,
            'completed_at',
        )->latest('completed_at')->limit(8)->get();
    }
}
