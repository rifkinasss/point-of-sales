<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActiveStoreContext;
use App\Services\BrowserTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OwnerTransactionQuery
{
    public function __construct(
        private readonly ActiveStoreContext $storeContext,
        private readonly BrowserTimezone $browserTimezone,
    ) {}

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    public function dateRange(array $filters): array
    {
        $today = CarbonImmutable::now($this->browserTimezone->current());
        $preset = $filters['datePreset'] ?? 'today';

        return match ($preset) {
            'yesterday' => [$today->subDay()->startOfDay()->utc(), $today->subDay()->endOfDay()->utc()],
            'last_7_days' => [$today->subDays(6)->startOfDay()->utc(), $today->endOfDay()->utc()],
            'last_30_days' => [$today->subDays(29)->startOfDay()->utc(), $today->endOfDay()->utc()],
            'custom' => $this->customRange($filters, $today),
            default => [$today->startOfDay()->utc(), $today->endOfDay()->utc()],
        };
    }

    public function paginate(User $owner, array $filters): LengthAwarePaginator
    {
        [$start, $end] = $this->dateRange($filters);
        return $this->filteredQuery($owner, $filters, $start, $end)
            ->with(['store', 'cashier', 'customer', 'items', 'payments'])
            ->latest('created_at')
            ->paginate((int) ($filters['perPage'] ?? 25));
    }

    /** @return array{transactions:int,gross:int,discount:int,refund:int,net:int,average:int} */
    public function summary(User $owner, array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $sales = $this->filteredQuery($owner, $filters, $start, $end)
            ->whereIn('sales.status', [SaleStatus::Paid, SaleStatus::Refunded]);
        $totals = (clone $sales)->selectRaw('COUNT(*) transactions, COALESCE(SUM(subtotal),0) gross, COALESCE(SUM(item_discount_total + promotion_discount_total + transaction_discount_total),0) discount, COALESCE(SUM(grand_total),0) total')->first();
        $refund = (int) Payment::query()->withoutGlobalScopes()
            ->where('payments.status', PaymentStatus::Refunded)
            ->whereBetween('payments.refunded_at', [$start, $end])
            ->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner, $filters['store'] ?? 'all')))
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
                $query->whereHas('sale', fn ($sale) => $sale->withoutGlobalScopes()->whereRaw('LOWER(invoice_number) LIKE ?', [$needle]));
            })
            ->sum('amount_due');
        $transactions = (int) ($totals->transactions ?? 0);
        $total = (int) ($totals->total ?? 0);

        return ['transactions' => $transactions, 'gross' => (int) ($totals->gross ?? 0), 'discount' => (int) ($totals->discount ?? 0), 'refund' => $refund, 'net' => max(0, $total - $refund), 'average' => $transactions > 0 ? intdiv($total, $transactions) : 0];
    }

    public function find(User $owner, string $publicId): ?Sale
    {
        return Sale::query()->withoutGlobalScopes()->with(['store', 'cashier', 'customer', 'items', 'payments'])
            ->whereIn('store_id', $this->storeIds($owner, 'all'))
            ->where('public_id', $publicId)->first();
    }

    /** @return array<int, Store> */
    public function stores(User $owner): array
    {
        return $this->storeContext->availableStores($owner)->all();
    }

    private function filteredQuery(User $owner, array $filters, CarbonImmutable $start, CarbonImmutable $end)
    {
        $query = Sale::query()->withoutGlobalScopes()->whereIn('sales.store_id', $this->storeIds($owner, $filters['store'] ?? 'all'))
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('sales.completed_at', [$start, $end])
                    ->orWhere(function ($query) use ($start, $end): void {
                        $query->whereNull('sales.completed_at')->whereBetween('sales.created_at', [$start, $end]);
                    });
            });

        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->where(function ($query) use ($needle): void {
                $query->whereRaw('LOWER(sales.invoice_number) LIKE ?', [$needle])
                    ->orWhereHas('customer', fn ($customer) => $customer->whereRaw('LOWER(name) LIKE ?', [$needle])->orWhereRaw('LOWER(code) LIKE ?', [$needle]))
                    ->orWhereHas('cashier', fn ($cashier) => $cashier->whereRaw('LOWER(name) LIKE ?', [$needle]))
                    ->orWhereHas('items', fn ($item) => $item->whereRaw('LOWER(product_name) LIKE ?', [$needle])->orWhereRaw('LOWER(sku) LIKE ?', [$needle]));
            });
        }
        if (filled($filters['status'] ?? null) && in_array($filters['status'], array_column(SaleStatus::cases(), 'value'), true)) $query->where('sales.status', $filters['status']);
        if (filled($filters['paymentMethod'] ?? null)) $query->whereHas('payments', fn ($payment) => $payment->where('method', $filters['paymentMethod']));
        if (filled($filters['cashier'] ?? null)) $query->whereHas('cashier', fn ($cashier) => $cashier->where('public_id', $filters['cashier']));

        return $query;
    }

    /** @return array<int,int> */
    private function storeIds(User $owner, string $identifier): array
    {
        $stores = $this->storeContext->availableStores($owner);
        if ($identifier !== 'all' && filled($identifier)) $stores = $stores->filter(fn (Store $store): bool => $store->public_id === $identifier);
        return $stores->pluck('id')->all();
    }

    private function customRange(array $filters, CarbonImmutable $today): array
    {
        try {
            $tz = $this->browserTimezone->current();
            return [CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateFrom'], $tz)->startOfDay()->utc(), CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateTo'], $tz)->endOfDay()->utc()];
        } catch (\Throwable) {
            return [$today->startOfDay()->utc(), $today->endOfDay()->utc()];
        }
    }
}
