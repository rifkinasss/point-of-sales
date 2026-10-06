<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use App\Services\BrowserTimezone;
use App\Services\CashBalanceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OwnerReportQuery
{
    public function __construct(
        private readonly ActiveStoreContext $storeContext,
        private readonly BrowserTimezone $browserTimezone,
        private readonly CashBalanceCalculator $cashCalculator,
    ) {}

    /** @return Collection<int, Store> */
    public function authorizedStores(User $owner): Collection
    {
        return $this->storeContext->authorizedStores($owner)->values();
    }

    /** @return array{start:CarbonImmutable,end:CarbonImmutable,label:string} */
    public function period(array $filters): array
    {
        $timezone = $this->browserTimezone->current();
        $today = CarbonImmutable::now($timezone);
        $preset = $filters['datePreset'] ?? 'last_30_days';

        $range = match ($preset) {
            'yesterday' => [$today->subDay()->startOfDay(), $today->subDay()->endOfDay(), 'Kemarin'],
            'last_7_days' => [$today->subDays(6)->startOfDay(), $today->endOfDay(), '7 hari terakhir'],
            'last_30_days' => [$today->subDays(29)->startOfDay(), $today->endOfDay(), '30 hari terakhir'],
            'this_month' => [$today->startOfMonth(), $today->endOfDay(), 'Bulan ini'],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth(), 'Bulan lalu'],
            'custom' => $this->customRange($filters, $timezone, $today),
            default => [$today->startOfDay(), $today->endOfDay(), 'Hari ini'],
        };

        return ['start' => $range[0]->utc(), 'end' => $range[1]->utc(), 'label' => $range[2]];
    }

    /** @return array<string,mixed> */
    public function report(User $owner, array $filters): array
    {
        $stores = $this->storesFor($owner, $filters['store'] ?? 'all');
        $period = $this->period($filters);
        $storeIds = $stores->pluck('id')->all();
        $sales = $this->salesBase($storeIds, $period);
        $payments = $this->completedPaymentsBase($storeIds, $period);
        $discountExpression = 'item_discount_total + promotion_discount_total + transaction_discount_total';
        $salesTotals = (clone $sales)->selectRaw("COUNT(*) transactions, COALESCE(SUM(subtotal),0) gross, COALESCE(SUM($discountExpression),0) discount, COALESCE(SUM(grand_total),0) total")->first();
        $refund = $this->refundAmount($storeIds, $period);
        $cash = (clone $payments)->where('payments.method', 'cash')->sum('amount_due');
        $nonCash = (clone $payments)->where('payments.method', '<>', 'cash')->sum('amount_due');
        $transactionCount = (int) ($salesTotals->transactions ?? 0);
        $total = (int) ($salesTotals->total ?? 0);

        return [
            'context' => ['store' => $filters['store'] ?? 'all', 'stores' => $stores->map(fn (Store $store): array => ['public_id' => $store->public_id, 'name' => $store->name, 'branch' => $store->branch_name ?: $store->branch_code])->all()],
            'period' => $period,
            'summary' => [
                'net' => max(0, $total - $refund),
                'gross' => (int) ($salesTotals->gross ?? 0),
                'transactions' => $transactionCount,
                'average' => $transactionCount > 0 ? intdiv($total, $transactionCount) : 0,
                'refund' => $refund,
                'discount' => (int) ($salesTotals->discount ?? 0),
                'cash' => (int) $cash,
                'nonCash' => (int) $nonCash,
            ],
            'trend' => $this->trend($storeIds, $period),
            'stores' => $this->storePerformance($stores, $period),
            'products' => $this->products($storeIds, $period, $filters['productSort'] ?? 'net'),
            'payments' => $this->payments($payments),
            'customers' => $this->customers($storeIds, $period),
            'promotions' => $this->promotions($storeIds, $period),
            'inventory' => $this->inventory($storeIds, $period),
            'cashReport' => $this->cashReport($storeIds, $period),
        ];
    }

    /** @return Collection<int, Store> */
    private function storesFor(User $owner, string $identifier): Collection
    {
        $stores = $this->authorizedStores($owner);
        if ($identifier === 'all' || $identifier === '') {
            return $stores;
        }

        return $stores->filter(fn (Store $store): bool => $store->public_id === $identifier)->values();
    }

    private function salesBase(array $storeIds, array $period)
    {
        return Sale::query()->withoutGlobalScopes()->whereIn('sales.store_id', $storeIds)
            ->whereBetween('sales.completed_at', [$period['start'], $period['end']])
            ->whereIn('sales.status', [SaleStatus::Paid, SaleStatus::Refunded]);
    }

    private function completedPaymentsBase(array $storeIds, array $period)
    {
        return Payment::query()->withoutGlobalScopes()->where('payments.status', PaymentStatus::Completed)
            ->whereBetween('payments.paid_at', [$period['start'], $period['end']])
            ->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereIn('store_id', $storeIds));
    }

    private function refundAmount(array $storeIds, array $period): int
    {
        return (int) Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Refunded)
            ->whereBetween('refunded_at', [$period['start'], $period['end']])
            ->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereIn('store_id', $storeIds))->sum('amount_due');
    }

    /** @return array<int,array<string,mixed>> */
    private function trend(array $storeIds, array $period): array
    {
        $days = $period['start']->diffInDays($period['end']);
        $format = $days < 2 ? 'YYYY-MM-DD HH24' : ($days > 90 ? 'YYYY-MM' : 'YYYY-MM-DD');
        $bucket = DB::connection()->getDriverName() === 'sqlite'
            ? ($days < 2 ? "strftime('%Y-%m-%d %H', completed_at)" : ($days > 90 ? "strftime('%Y-%m', completed_at)" : "strftime('%Y-%m-%d', completed_at)"))
            : "to_char(completed_at, '$format')";
        $rows = $this->salesBase($storeIds, $period)->selectRaw("$bucket bucket, COUNT(*) transactions, COALESCE(SUM(grand_total),0) total")->groupBy('bucket')->orderBy('bucket')->get();

        return $rows->map(fn ($row): array => ['bucket' => $row->bucket, 'transactions' => (int) $row->transactions, 'total' => (int) $row->total])->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function storePerformance(Collection $stores, array $period): array
    {
        if ($stores->isEmpty()) return [];
        $ids = $stores->pluck('id')->all();
        $sales = $this->salesBase($ids, $period)->select('store_id')->selectRaw('COUNT(*) transactions, COALESCE(SUM(subtotal),0) gross, COALESCE(SUM(item_discount_total + promotion_discount_total + transaction_discount_total),0) discount, COALESCE(SUM(grand_total),0) total')->groupBy('store_id')->get()->keyBy('store_id');
        $payments = $this->completedPaymentsBase($ids, $period)->join('sales', 'sales.id', '=', 'payments.sale_id')->select('sales.store_id')->selectRaw("COALESCE(SUM(CASE WHEN payments.method = 'cash' THEN payments.amount_due ELSE 0 END),0) cash, COALESCE(SUM(CASE WHEN payments.method <> 'cash' THEN payments.amount_due ELSE 0 END),0) non_cash")->groupBy('sales.store_id')->get()->keyBy('store_id');
        $refunds = Payment::query()->withoutGlobalScopes()->where('payments.status', PaymentStatus::Refunded)->whereBetween('payments.refunded_at', [$period['start'], $period['end']])->join('sales', 'sales.id', '=', 'payments.sale_id')->whereIn('sales.store_id', $ids)->select('sales.store_id')->selectRaw('COALESCE(SUM(payments.amount_due),0) refund')->groupBy('sales.store_id')->pluck('refund', 'store_id');

        return $stores->map(function (Store $store) use ($sales, $payments, $refunds): array {
            $row = $sales->get($store->id); $payment = $payments->get($store->id); $refund = (int) ($refunds[$store->id] ?? 0); $total = (int) ($row->total ?? 0);
            return ['public_id' => $store->public_id, 'store' => $store->name, 'branch' => $store->branch_name ?: $store->branch_code, 'transactions' => (int) ($row->transactions ?? 0), 'gross' => (int) ($row->gross ?? 0), 'discount' => (int) ($row->discount ?? 0), 'refund' => $refund, 'net' => max(0, $total - $refund), 'average' => (int) ($row->transactions ?? 0) > 0 ? intdiv($total, (int) $row->transactions) : 0, 'cash' => (int) ($payment->cash ?? 0), 'nonCash' => (int) ($payment->non_cash ?? 0)];
        })->sortByDesc('net')->values()->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function products(array $storeIds, array $period, string $sort): array
    {
        $query = SaleItem::query()->withoutGlobalScopes()->join('sales', 'sales.id', '=', 'sale_items.sale_id')->whereIn('sales.store_id', $storeIds)->whereBetween('sales.completed_at', [$period['start'], $period['end']])->whereIn('sales.status', [SaleStatus::Paid, SaleStatus::Refunded])->select('sale_items.product_name', 'sale_items.sku')->selectRaw('SUM(quantity) quantity, SUM(quantity * (unit_price + modifier_total)) gross, SUM(line_total) net, SUM(discount_amount) discount, COUNT(DISTINCT sale_id) transactions')->groupBy('sale_items.product_name', 'sale_items.sku');
        $rows = $query->get();
        if ($sort === 'quantity') $rows = $rows->sortByDesc('quantity');
        else $rows = $rows->sortByDesc('net');
        return $rows->values()->map(fn ($row): array => ['product' => $row->product_name, 'sku' => $row->sku, 'quantity' => (int) $row->quantity, 'gross' => (int) $row->gross, 'net' => (int) $row->net, 'discount' => (int) $row->discount, 'transactions' => (int) $row->transactions])->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function payments($query): array
    {
        $rows = $query->select('method')->selectRaw('COUNT(*) count, COALESCE(SUM(amount_due),0) amount')->groupBy('method')->get();
        $total = (int) $rows->sum('amount');
        return $rows->map(fn ($row): array => ['method' => $row->method instanceof \BackedEnum ? $row->method->value : (string) $row->method, 'count' => (int) $row->count, 'amount' => (int) $row->amount, 'percentage' => $total ? round($row->amount * 100 / $total, 1) : 0])->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function customers(array $storeIds, array $period): array
    {
        return Sale::query()->withoutGlobalScopes()->join('customers', 'customers.id', '=', 'sales.customer_id')->whereIn('sales.store_id', $storeIds)->whereNotNull('sales.customer_id')->whereBetween('sales.completed_at', [$period['start'], $period['end']])->whereIn('sales.status', [SaleStatus::Paid, SaleStatus::Refunded])->select('customers.public_id', 'customers.name')->selectRaw('COUNT(sales.id) transactions, COALESCE(SUM(sales.grand_total),0) spend, MAX(sales.completed_at) last_transaction')->groupBy('customers.public_id', 'customers.name')->orderByDesc('spend')->limit(100)->get()->map(fn ($row): array => ['public_id' => $row->public_id, 'name' => $row->name, 'transactions' => (int) $row->transactions, 'spend' => (int) $row->spend, 'average' => $row->transactions ? intdiv((int) $row->spend, (int) $row->transactions) : 0, 'last_transaction' => $row->last_transaction])->all();
    }

    /** @return array<int,array<string,mixed>> */
    private function promotions(array $storeIds, array $period): array
    {
        return Sale::query()->withoutGlobalScopes()->whereIn('store_id', $storeIds)->whereNotNull('promotion_code')->where('promotion_code', '<>', '')->whereBetween('completed_at', [$period['start'], $period['end']])->whereIn('status', [SaleStatus::Paid, SaleStatus::Refunded])->select('promotion_code')->selectRaw('COUNT(*) usage_count, COALESCE(SUM(promotion_discount_total),0) discount, COALESCE(SUM(grand_total),0) net_sales')->groupBy('promotion_code')->orderByDesc('usage_count')->get()->map(fn ($row): array => ['promotion' => $row->promotion_code, 'usage' => (int) $row->usage_count, 'discount' => (int) $row->discount, 'net' => (int) $row->net_sales])->all();
    }

    /** @return array<string,mixed> */
    private function inventory(array $storeIds, array $period): array
    {
        $stock = Inventory::query()->join('products', 'products.id', '=', 'inventories.product_id')->whereIn('products.store_id', $storeIds)->selectRaw('COUNT(*) products, SUM(CASE WHEN inventories.quantity = 0 THEN 1 ELSE 0 END) out_of_stock, SUM(CASE WHEN inventories.quantity > 0 AND inventories.quantity <= products.minimum_stock THEN 1 ELSE 0 END) low_stock, COALESCE(SUM(inventories.quantity),0) units')->first();
        $movement = StockMovement::query()->withoutGlobalScopes()->whereIn('store_id', $storeIds)->whereBetween('occurred_at', [$period['start'], $period['end']])->selectRaw('COALESCE(SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END),0) stock_in, COALESCE(SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END),0) stock_out')->first();
        return ['products' => (int) ($stock->products ?? 0), 'outOfStock' => (int) ($stock->out_of_stock ?? 0), 'lowStock' => (int) ($stock->low_stock ?? 0), 'units' => (int) ($stock->units ?? 0), 'stockIn' => (int) ($movement->stock_in ?? 0), 'stockOut' => (int) ($movement->stock_out ?? 0)];
    }

    /** @return array<string,mixed> */
    private function cashReport(array $storeIds, array $period): array
    {
        $movements = CashMovement::query()->withoutGlobalScopes()->whereIn('store_id', $storeIds)->whereBetween('created_at', [$period['start'], $period['end']])->select('type')->selectRaw('COALESCE(SUM(amount),0) amount')->groupBy('type')->pluck('amount', 'type');
        $opening = (int) ($movements->get('opening') ?? 0); $sales = (int) ($movements->get('sale') ?? 0); $cashIn = (int) ($movements->get('cash_in') ?? 0); $refunds = (int) ($movements->get('refund') ?? 0); $cashOut = (int) ($movements->get('cash_out') ?? 0);
        $expected = $this->cashCalculator->expectedFromParts($opening, $sales, $cashIn, $refunds, $cashOut);
        $closed = CashierShift::query()->withoutGlobalScopes()->whereIn('store_id', $storeIds)->whereNotNull('closed_at')->whereBetween('closed_at', [$period['start'], $period['end']])->selectRaw('COALESCE(SUM(closing_cash_actual),0) actual, COALESCE(SUM(cash_difference),0) variance')->first();
        return ['opening' => $opening, 'sales' => $sales, 'cashIn' => $cashIn, 'refunds' => $refunds, 'cashOut' => $cashOut, 'expected' => $expected, 'actual' => (int) ($closed->actual ?? 0), 'variance' => (int) ($closed->variance ?? 0)];
    }

    private function customRange(array $filters, string $timezone, CarbonImmutable $today): array
    {
        try {
            $start = CarbonImmutable::createFromFormat('Y-m-d', (string) ($filters['dateFrom'] ?? ''), $timezone)->startOfDay();
            $end = CarbonImmutable::createFromFormat('Y-m-d', (string) ($filters['dateTo'] ?? ''), $timezone)->endOfDay();
            return [$start, $end, $start->isoFormat('D MMM YYYY').' - '.$end->isoFormat('D MMM YYYY')];
        } catch (\Throwable) {
            return [$today->startOfDay(), $today->endOfDay(), 'Hari ini'];
        }
    }
}
