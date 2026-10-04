<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ManagerDashboardQuery
{
    /** @return array<string, mixed> */
    public function dashboard(User $manager): array
    {
        $timezone = $manager->store?->timezone ?: config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone);
        $start = $today->startOfDay()->utc();
        $end = $today->endOfDay()->utc();
        $paidSales = Sale::query()->withoutGlobalScopes()
            ->where('store_id', $manager->store_id)
            ->where('status', SaleStatus::Paid)
            ->whereBetween('completed_at', [$start, $end]);

        $transactionCount = (clone $paidSales)->count();
        $grossSales = (int) (clone $paidSales)->sum('subtotal');
        $discount = (int) (clone $paidSales)->sum('item_discount_total')
            + (int) (clone $paidSales)->sum('promotion_discount_total')
            + (int) (clone $paidSales)->sum('transaction_discount_total');
        $grandTotal = (int) (clone $paidSales)->sum('grand_total');
        $refund = (int) Payment::query()->withoutGlobalScopes()
            ->where('status', \App\Enums\PaymentStatus::Refunded)
            ->whereBetween('refunded_at', [$start, $end])
            ->whereHas('sale', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $manager->store_id))
            ->sum('amount_due');
        $netSales = max(0, $grandTotal - $refund);

        $payments = Payment::query()->withoutGlobalScopes()
            ->where('status', \App\Enums\PaymentStatus::Completed)
            ->whereBetween('paid_at', [$start, $end])
            ->whereHas('sale', fn ($query) => $query->withoutGlobalScopes()->where('store_id', $manager->store_id))
            ->get(['method', 'amount_due']);
        $paymentBreakdown = $payments->groupBy(fn (Payment $payment): string => $payment->method->value)
            ->map(fn (Collection $rows): int => (int) $rows->sum('amount_due'))
            ->all();

        $activeShifts = CashierShift::query()->withoutGlobalScopes()
            ->with(['cashier', 'cashMovements'])
            ->where('store_id', $manager->store_id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->get()
            ->map(fn (CashierShift $shift): array => $this->shiftSummary($shift))
            ->values();

        return [
            'transactionCount' => $transactionCount,
            'grossSales' => $grossSales,
            'discount' => $discount,
            'refund' => $refund,
            'netSales' => $netSales,
            'averageTransaction' => $transactionCount > 0 ? intdiv($netSales, $transactionCount) : 0,
            'cashSales' => $paymentBreakdown['cash'] ?? 0,
            'openShifts' => $activeShifts->count(),
            'lowStock' => Product::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)
                ->where('is_active', true)
                ->whereHas('inventory', fn ($query) => $query->whereColumn('quantity', '<=', 'products.minimum_stock'))->count(),
            'paymentBreakdown' => $paymentBreakdown,
            'activeShifts' => $activeShifts,
            'cashActivity' => $this->cashActivity($manager, $start, $end),
            'lowStockProducts' => Product::query()->withoutGlobalScopes()->with(['inventory', 'category'])
                ->where('store_id', $manager->store_id)->where('is_active', true)
                ->whereHas('inventory', fn ($query) => $query->whereColumn('quantity', '<=', 'products.minimum_stock'))
                ->orderBy('name')->limit(5)->get(),
            'recentSales' => $this->recentSales($manager),
        ];
    }

    /** @return array<string, int> */
    public function summary(User $manager): array
    {
        $dashboard = $this->dashboard($manager);

        return [
            'salesToday' => $dashboard['transactionCount'],
            'revenueToday' => $dashboard['netSales'],
            'openShifts' => $dashboard['openShifts'],
            'lowStock' => $dashboard['lowStock'],
        ];
    }

    public function recentSales(User $manager): Collection
    {
        return Sale::query()->withoutGlobalScopes()->with(['cashier', 'customer', 'payments'])
            ->where('store_id', $manager->store_id)
            ->latest('created_at')->limit(8)->get();
    }

    /** @return array{opening: int, sales: int, cashIn: int, cashOut: int, refunds: int, expected: int} */
    private function cashActivity(User $manager, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $movements = CashMovement::query()->withoutGlobalScopes()
            ->where('store_id', $manager->store_id)
            ->whereBetween('created_at', [$start, $end])
            ->get()->groupBy(fn (CashMovement $movement): string => $movement->type->value);
        $opening = (int) ($movements->get('opening')?->sum('amount') ?? 0);
        $sales = (int) ($movements->get('sale')?->sum('amount') ?? 0);
        $cashIn = (int) ($movements->get('cash_in')?->sum('amount') ?? 0);
        $cashOut = (int) ($movements->get('cash_out')?->sum('amount') ?? 0);
        $refunds = (int) ($movements->get('refund')?->sum('amount') ?? 0);

        return compact('opening', 'sales', 'cashIn', 'cashOut', 'refunds') + [
            'expected' => $opening + $sales + $cashIn - $cashOut - $refunds,
        ];
    }

    /** @return array<string, mixed> */
    private function shiftSummary(CashierShift $shift): array
    {
        $movements = $shift->cashMovements->groupBy(fn (CashMovement $movement): string => $movement->type->value);
        $opening = (int) ($movements->get('opening')?->sum('amount') ?? 0);
        $sales = (int) ($movements->get('sale')?->sum('amount') ?? 0);
        $cashIn = (int) ($movements->get('cash_in')?->sum('amount') ?? 0);
        $cashOut = (int) ($movements->get('cash_out')?->sum('amount') ?? 0);
        $refunds = (int) ($movements->get('refund')?->sum('amount') ?? 0);

        return [
            'cashier' => $shift->cashier,
            'openedAt' => $shift->opened_at,
            'durationMinutes' => $shift->opened_at ? max(0, $shift->opened_at->diffInMinutes(now())) : 0,
            'opening' => $opening,
            'sales' => $sales,
            'cashIn' => $cashIn,
            'cashOut' => $cashOut,
            'refunds' => $refunds,
            'expected' => $opening + $sales + $cashIn - $cashOut - $refunds,
        ];
    }
}
