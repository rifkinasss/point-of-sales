<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use App\Services\ActiveStoreContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagerReportQuery
{
    public function __construct(private readonly CashBalanceCalculator $cashCalculator) {}

    /** @return array{start:CarbonImmutable,end:CarbonImmutable,label:string} */
    public function period(User $manager, array $filters): array
    {
        $timezone = $manager->store?->timezone ?: config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone);
        $preset = $filters['datePreset'] ?? 'today';

        if ($preset === 'yesterday') {
            $day = $today->subDay();

            return ['start' => $day->startOfDay()->utc(), 'end' => $day->endOfDay()->utc(), 'label' => $day->isoFormat('D MMMM YYYY')];
        }

        if ($preset === 'last_7_days') {
            return ['start' => $today->subDays(6)->startOfDay()->utc(), 'end' => $today->endOfDay()->utc(), 'label' => '7 hari terakhir'];
        }

        if ($preset === 'last_30_days') {
            return ['start' => $today->subDays(29)->startOfDay()->utc(), 'end' => $today->endOfDay()->utc(), 'label' => '30 hari terakhir'];
        }

        if ($preset === 'this_month') {
            return ['start' => $today->startOfMonth()->utc(), 'end' => $today->endOfDay()->utc(), 'label' => 'Bulan ini'];
        }

        if ($preset === 'custom' && filled($filters['dateFrom'] ?? null) && filled($filters['dateTo'] ?? null)) {
            try {
                $start = CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateFrom'], $timezone)->startOfDay();
                $end = CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateTo'], $timezone)->endOfDay();

                return ['start' => $start->utc(), 'end' => $end->utc(), 'label' => $start->isoFormat('D MMM YYYY').' - '.$end->isoFormat('D MMM YYYY')];
            } catch (\Throwable) {
                // Component validation reports malformed custom dates.
            }
        }

        return ['start' => $today->startOfDay()->utc(), 'end' => $today->endOfDay()->utc(), 'label' => 'Hari ini'];
    }

    /** @return array<string, mixed> */
    public function report(User $manager, string $report, array $filters): array
    {
        return match ($report) {
            'sales' => $this->sales($manager, $filters),
            'payments' => $this->payments($manager, $filters),
            'shifts' => $this->shifts($manager, $filters),
            'cash' => $this->cash($manager, $filters),
            'products' => $this->products($manager, $filters),
            'inventory' => $this->inventory($manager, $filters),
            default => $this->overview($manager),
        };
    }

    /** @return array<string, mixed> */
    public function overview(User $manager): array
    {
        $filters = ['datePreset' => 'today'];
        $sales = $this->sales($manager, $filters);
        $payments = $this->payments($manager, $filters);
        $shifts = $this->shifts($manager, $filters);
        $products = $this->products($manager, $filters);

        return [
            'title' => 'Ringkasan operasional',
            'period' => $sales['period'],
            'summary' => [
                'transaksi' => $sales['summary']['transactions'],
                'penjualan bersih' => $sales['summary']['net'],
                'pembayaran selesai' => $payments['summary']['completed'],
                'shift aktif' => $shifts['summary']['active'],
            ],
            'reports' => [
                ['key' => 'sales', 'title' => 'Laporan Penjualan', 'description' => 'Gross, diskon, refund, dan penjualan bersih.', 'icon' => 'heroicon-o-chart-bar'],
                ['key' => 'payments', 'title' => 'Laporan Pembayaran', 'description' => 'Breakdown pembayaran termasuk split payment.', 'icon' => 'heroicon-o-credit-card'],
                ['key' => 'shifts', 'title' => 'Laporan Shift', 'description' => 'Durasi, opening cash, dan status shift kasir.', 'icon' => 'heroicon-o-clock'],
                ['key' => 'cash', 'title' => 'Rekonsiliasi Kas', 'description' => 'Expected cash, actual closed cash, dan variance.', 'icon' => 'heroicon-o-banknotes'],
                ['key' => 'products', 'title' => 'Performa Produk', 'description' => 'Qty terjual dan kontribusi revenue dari snapshot transaksi.', 'icon' => 'heroicon-o-cube'],
                ['key' => 'inventory', 'title' => 'Pergerakan Stok', 'description' => 'Stock in, stock out, refund, dan adjustment.', 'icon' => 'heroicon-o-arrows-right-left'],
            ],
            'products' => $products['rows'],
        ];
    }

    /** @return array<string, mixed> */
    private function sales(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $base = Sale::query()->withoutGlobalScopes()->whereIn('sales.store_id', $this->storeIds($manager))
            ->whereBetween('completed_at', [$period['start'], $period['end']])->whereIn('status', [SaleStatus::Paid, SaleStatus::Refunded]);
        $this->applyCashierSearch($base, $filters);
        $summary = (clone $base)->selectRaw('COUNT(*) as transactions, COALESCE(SUM(subtotal), 0) as gross, COALESCE(SUM(item_discount_total + promotion_discount_total + transaction_discount_total), 0) as discount, COALESCE(SUM(grand_total), 0) as total')->first();
        $refund = Payment::query()->withoutGlobalScopes()->where('status', PaymentStatus::Refunded)->whereBetween('refunded_at', [$period['start'], $period['end']])->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager)))->sum('amount_due');
        $rows = (clone $base)->with(['cashier', 'customer'])->addSelect(['refund_amount' => Payment::query()->selectRaw('COALESCE(SUM(amount_due), 0)')->whereColumn('sale_id', 'sales.id')->where('status', PaymentStatus::Refunded)])->latest('completed_at')->get()->map(function (Sale $sale): array {
            $discount = $sale->item_discount_total + $sale->promotion_discount_total + $sale->transaction_discount_total;

            return ['date' => $sale->completed_at, 'invoice' => $sale->invoice_number, 'cashier' => $sale->cashier?->name ?? '-', 'customer' => $sale->customer?->name ?? 'Walk-in Customer', 'gross' => (int) $sale->subtotal, 'discount' => $discount, 'refund' => (int) ($sale->refund_amount ?? 0), 'net' => max(0, (int) $sale->grand_total - (int) ($sale->refund_amount ?? 0)), 'status' => $sale->status->value, 'public_id' => $sale->public_id];
        })->all();

        return ['key' => 'sales', 'title' => 'Laporan Penjualan', 'description' => 'Penjualan completed dan refunded pada periode yang dipilih.', 'period' => $period, 'summary' => ['gross' => (int) $summary->gross, 'discount' => (int) $summary->discount, 'refund' => (int) $refund, 'net' => max(0, (int) $summary->total - (int) $refund, ), 'transactions' => (int) $summary->transactions, 'average' => (int) $summary->transactions > 0 ? intdiv((int) $summary->total, (int) $summary->transactions) : 0], 'columns' => ['date' => 'Tanggal', 'invoice' => 'Invoice', 'cashier' => 'Kasir', 'customer' => 'Customer', 'gross' => 'Gross', 'discount' => 'Diskon', 'refund' => 'Refund', 'net' => 'Net', 'status' => 'Status', 'public_id' => ''], 'rows' => $rows, 'printable' => true];
    }

    /** @return array<string, mixed> */
    private function payments(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $base = Payment::query()->withoutGlobalScopes()->whereBetween('payments.created_at', [$period['start'], $period['end']])->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager)));
        $statusTotals = (clone $base)->select('status')->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_due), 0) as amount')->groupBy('status')->get()->keyBy('status');
        $breakdown = (clone $base)->where('status', PaymentStatus::Completed)->select('method')->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_due), 0) as amount')->groupBy('method')->get();
        $total = (int) $breakdown->sum('amount');
        $rows = $breakdown->map(fn ($row): array => ['method' => $row->method instanceof \BackedEnum ? $row->method->value : (string) $row->method, 'count' => (int) $row->count, 'amount' => (int) $row->amount, 'percentage' => $total > 0 ? round(((int) $row->amount / $total) * 100, 1) : 0])->values()->all();

        return ['key' => 'payments', 'title' => 'Laporan Pembayaran', 'description' => 'Allocation completed dihitung per payment, sehingga split payment tetap terlihat.', 'period' => $period, 'summary' => ['completed' => $total, 'completedCount' => (int) ($statusTotals->get(PaymentStatus::Completed->value)?->count ?? 0), 'pending' => (int) ($statusTotals->get(PaymentStatus::Pending->value)?->amount ?? 0), 'failed' => (int) ($statusTotals->get(PaymentStatus::Failed->value)?->amount ?? 0), 'refunded' => (int) ($statusTotals->get(PaymentStatus::Refunded->value)?->amount ?? 0)], 'columns' => ['method' => 'Metode', 'count' => 'Pembayaran', 'amount' => 'Jumlah', 'percentage' => 'Porsi'], 'rows' => $rows, 'statusTotals' => $statusTotals->map(fn ($row): array => ['count' => (int) $row->count, 'amount' => (int) $row->amount])->all(), 'printable' => true];
    }

    /** @return array<string, mixed> */
    private function shifts(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $shifts = CashierShift::query()->withoutGlobalScopes()->with('cashier')->whereIn('store_id', $this->storeIds($manager))->whereBetween('opened_at', [$period['start'], $period['end']])->latest('opened_at')->get();
        $summaries = $this->cashCalculator->summaries($shifts);
        $rows = $shifts->map(function (CashierShift $shift) use ($summaries): array {
            $summary = $summaries->get($shift->id, []);
            $duration = $shift->opened_at && ($shift->closed_at ?? now()) ? $shift->opened_at->diffInMinutes($shift->closed_at ?? now()) : 0;

            return ['cashier' => $shift->cashier?->name ?? '-', 'opened' => $shift->opened_at, 'closed' => $shift->closed_at, 'duration' => $duration, 'opening' => (int) ($summary['opening'] ?? 0), 'expected' => (int) ($summary['expected'] ?? 0), 'actual' => $shift->closed_at ? (int) $shift->closing_cash_actual : null, 'variance' => $shift->closed_at ? (int) $shift->cash_difference : null, 'status' => $shift->status->value, 'public_id' => $shift->public_id];
        })->all();
        $closed = collect($rows)->filter(fn (array $row): bool => $row['closed'] !== null);

        return ['key' => 'shifts', 'title' => 'Laporan Shift', 'description' => 'Ringkasan shift kasir dalam periode operasional.', 'period' => $period, 'summary' => ['opened' => count($rows), 'closed' => $closed->count(), 'active' => count($rows) - $closed->count(), 'opening' => (int) collect($rows)->sum('opening'), 'expected' => (int) collect($rows)->sum('expected'), 'actual' => (int) $closed->sum('actual'), 'variance' => (int) $closed->sum('variance'), 'averageDuration' => (int) (collect($rows)->avg('duration') ?? 0)], 'columns' => ['cashier' => 'Kasir', 'opened' => 'Dibuka', 'closed' => 'Ditutup', 'duration' => 'Durasi', 'opening' => 'Opening', 'expected' => 'Expected', 'actual' => 'Actual', 'variance' => 'Variance', 'status' => 'Status', 'public_id' => ''], 'rows' => $rows, 'printable' => true];
    }

    /** @return array<string, mixed> */
    private function cash(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $movementTotals = CashMovement::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager))->whereBetween('created_at', [$period['start'], $period['end']])->select('type')->selectRaw('COALESCE(SUM(amount), 0) as amount')->groupBy('type')->pluck('amount', 'type');
        $opening = (int) ($movementTotals->get('opening') ?? 0); $sales = (int) ($movementTotals->get('sale') ?? 0); $cashIn = (int) ($movementTotals->get('cash_in') ?? 0); $refunds = (int) ($movementTotals->get('refund') ?? 0); $cashOut = (int) ($movementTotals->get('cash_out') ?? 0);
        $closed = CashierShift::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager))->whereNotNull('closed_at')->whereBetween('closed_at', [$period['start'], $period['end']])->selectRaw('COALESCE(SUM(closing_cash_actual), 0) as actual, COALESCE(SUM(cash_difference), 0) as variance')->first();
        $expected = $this->cashCalculator->expectedFromParts($opening, $sales, $cashIn, $refunds, $cashOut);
        $rows = CashierShift::query()->withoutGlobalScopes()->with('cashier')->whereIn('store_id', $this->storeIds($manager))->whereNotNull('closed_at')->whereBetween('closed_at', [$period['start'], $period['end']])->latest('closed_at')->get()->map(function (CashierShift $shift): array { $summary = $this->cashCalculator->summary($shift); return ['cashier' => $shift->cashier?->name ?? '-', 'closed' => $shift->closed_at, 'expected' => $summary['expected'], 'actual' => (int) $shift->closing_cash_actual, 'variance' => (int) $shift->cash_difference, 'status' => (int) $shift->cash_difference === 0 ? 'sesuai' : ((int) $shift->cash_difference < 0 ? 'kurang' : 'lebih'), 'public_id' => $shift->public_id]; })->all();

        return ['key' => 'cash', 'title' => 'Rekonsiliasi Kas', 'description' => 'Expected cash memakai CashBalanceCalculator. Actual hanya menjumlahkan shift yang sudah ditutup.', 'period' => $period, 'summary' => ['opening' => $opening, 'sales' => $sales, 'cashIn' => $cashIn, 'refunds' => $refunds, 'cashOut' => $cashOut, 'expected' => $expected, 'actual' => (int) ($closed?->actual ?? 0), 'variance' => (int) ($closed?->variance ?? 0)], 'columns' => ['cashier' => 'Kasir', 'closed' => 'Ditutup', 'expected' => 'Expected', 'actual' => 'Actual', 'variance' => 'Variance', 'status' => 'Status', 'public_id' => ''], 'rows' => $rows, 'printable' => true];
    }

    /** @return array<string, mixed> */
    private function products(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $rows = SaleItem::query()->withoutGlobalScopes()->join('sales', 'sales.id', '=', 'sale_items.sale_id')->leftJoin('products', 'products.id', '=', 'sale_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->whereIn('sales.store_id', $this->storeIds($manager))->where('sales.status', SaleStatus::Paid)->whereBetween('sales.completed_at', [$period['start'], $period['end']])->select('sale_items.product_name', 'sale_items.sku', 'categories.name as category')->selectRaw('SUM(sale_items.quantity) as quantity, SUM(sale_items.line_total) as revenue, SUM(sale_items.discount_amount) as discount, COUNT(DISTINCT sale_items.sale_id) as transactions')->groupBy('sale_items.product_name', 'sale_items.sku', 'categories.name')->orderByDesc('revenue')->get();
        $sort = $filters['productSort'] ?? 'revenue';
        $rows = $sort === 'quantity' ? $rows->sortByDesc('quantity') : ($sort === 'lowest_quantity' ? $rows->sortBy('quantity') : $rows);
        $mapped = $rows->values()->map(fn ($row): array => ['product' => $row->product_name, 'sku' => $row->sku, 'category' => $row->category ?? 'Tidak tersedia pada snapshot', 'quantity' => (int) $row->quantity, 'revenue' => (int) $row->revenue, 'discount' => (int) $row->discount, 'transactions' => (int) $row->transactions])->all();

        return ['key' => 'products', 'title' => 'Performa Produk', 'description' => 'Revenue dan qty menggunakan snapshot SaleItem. Kategori memakai master saat ini bila tersedia.', 'period' => $period, 'summary' => ['products' => count($mapped), 'quantity' => (int) collect($mapped)->sum('quantity'), 'revenue' => (int) collect($mapped)->sum('revenue')], 'columns' => ['product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'quantity' => 'Qty', 'revenue' => 'Revenue', 'discount' => 'Diskon', 'transactions' => 'Transaksi'], 'rows' => $mapped, 'printable' => true];
    }

    /** @return array<string, mixed> */
    private function inventory(User $manager, array $filters): array
    {
        $period = $this->period($manager, $filters);
        $movements = StockMovement::query()->withoutGlobalScopes()->with(['product', 'performedBy'])->whereIn('store_id', $this->storeIds($manager))->whereBetween('occurred_at', [$period['start'], $period['end']])->latest('occurred_at')->get();
        $rows = $movements->map(fn (StockMovement $movement): array => ['date' => $movement->occurred_at, 'product' => $movement->product?->name ?? '-', 'sku' => $movement->product?->sku ?? '-', 'movement_type' => $movement->type->value, 'quantity' => (int) $movement->quantity_change, 'reference' => class_basename((string) $movement->reference_type), 'actor' => $movement->performedBy?->name ?? '-'])->all();
        $summary = $movements->groupBy(fn (StockMovement $movement): string => $movement->type->value)->map(fn (Collection $group): int => (int) $group->sum('quantity_change'));

        return ['key' => 'inventory', 'title' => 'Pergerakan Stok', 'description' => 'Pergerakan aktual dari stock movement ledger, tanpa mengekspos ID internal.', 'period' => $period, 'summary' => ['stockIn' => (int) $movements->filter(fn ($m) => $m->quantity_change > 0)->sum('quantity_change'), 'stockOut' => abs((int) $movements->filter(fn ($m) => $m->quantity_change < 0)->sum('quantity_change')), 'sale' => abs((int) ($summary->get(StockMovementType::SaleOut->value) ?? 0)), 'refund' => (int) ($summary->get(StockMovementType::SaleReturnIn->value) ?? 0), 'adjustmentIn' => (int) ($summary->get(StockMovementType::AdjustmentIn->value) ?? 0), 'adjustmentOut' => abs((int) ($summary->get(StockMovementType::AdjustmentOut->value) ?? 0))], 'columns' => ['date' => 'Tanggal', 'product' => 'Produk', 'sku' => 'SKU', 'movement_type' => 'Tipe', 'quantity' => 'Qty', 'reference' => 'Referensi', 'actor' => 'Aktor'], 'rows' => $rows, 'printable' => true];
    }

    private function applyCashierSearch($query, array $filters): void
    {
        if (filled($filters['cashier'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['cashier'])).'%';
            $query->whereHas('cashier', fn ($cashier) => $cashier->whereRaw('LOWER(name) LIKE ?', [$needle]));
        }
    }

    /** @return array<int, int> */
    private function storeIds(User $manager): array
    {
        return app(ActiveStoreContext::class)->scope($manager)->ids();
    }
}
