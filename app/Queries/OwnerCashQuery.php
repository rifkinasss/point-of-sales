<?php

namespace App\Queries;

use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use App\Services\BrowserTimezone;
use App\Services\CashBalanceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OwnerCashQuery
{
    public function __construct(private readonly ActiveStoreContext $storeContext, private readonly BrowserTimezone $browserTimezone, private readonly CashBalanceCalculator $calculator) {}

    /** @return array{0:CarbonImmutable,1:CarbonImmutable} */
    public function dateRange(array $filters): array
    {
        $today = CarbonImmutable::now($this->browserTimezone->current());
        return match ($filters['datePreset'] ?? 'last_30_days') {
            'today' => [$today->startOfDay()->utc(), $today->endOfDay()->utc()],
            'yesterday' => [$today->subDay()->startOfDay()->utc(), $today->subDay()->endOfDay()->utc()],
            'last_7_days' => [$today->subDays(6)->startOfDay()->utc(), $today->endOfDay()->utc()],
            'custom' => $this->customRange($filters, $today),
            default => [$today->subDays(29)->startOfDay()->utc(), $today->endOfDay()->utc()],
        };
    }

    public function summary(User $owner, array $filters): array
    {
        [$start, $end] = $this->dateRange($filters); $ids = $this->storeIds($owner, $filters['store'] ?? 'all');
        $movements = CashMovement::withoutGlobalScopes()->whereIn('store_id', $ids)->whereBetween('created_at', [$start, $end])->select('type')->selectRaw('COALESCE(SUM(amount),0) amount')->groupBy('type')->pluck('amount', 'type');
        $opening = (int) ($movements->get(CashMovementType::Opening->value) ?? 0); $sales = (int) ($movements->get(CashMovementType::Sale->value) ?? 0); $cashIn = (int) ($movements->get(CashMovementType::CashIn->value) ?? 0); $refunds = (int) ($movements->get(CashMovementType::Refund->value) ?? 0); $cashOut = (int) ($movements->get(CashMovementType::CashOut->value) ?? 0);
        $closed = CashierShift::withoutGlobalScopes()->whereIn('store_id', $ids)->whereNotNull('closed_at')->whereBetween('closed_at', [$start, $end])->selectRaw('COALESCE(SUM(closing_cash_actual),0) actual, COALESCE(SUM(cash_difference),0) variance')->first();
        return ['opening' => $opening, 'sales' => $sales, 'cashIn' => $cashIn, 'refunds' => $refunds, 'cashOut' => $cashOut, 'expected' => $this->calculator->expectedFromParts($opening, $sales, $cashIn, $refunds, $cashOut), 'actual' => (int) ($closed->actual ?? 0), 'variance' => (int) ($closed->variance ?? 0)];
    }

    public function paginate(User $owner, array $filters): LengthAwarePaginator
    {
        [$start, $end] = $this->dateRange($filters); $query = CashierShift::withoutGlobalScopes()->with(['store', 'cashier'])->whereIn('store_id', $this->storeIds($owner, $filters['store'] ?? 'all'))->whereBetween('opened_at', [$start, $end]);
        if (filled($filters['status'] ?? null) && in_array($filters['status'], array_column(ShiftStatus::cases(), 'value'), true)) $query->where('status', $filters['status']);
        if (filled($filters['cashier'] ?? null)) $query->whereHas('cashier', fn ($cashier) => $cashier->where('public_id', $filters['cashier']));
        $paginator = $query->latest('opened_at')->paginate((int) ($filters['perPage'] ?? 25));
        $summaries = $this->calculator->summaries(collect($paginator->items()));
        foreach ($paginator->getCollection() as $shift) $shift->setAttribute('cash_summary', $summaries->get($shift->id));
        return $paginator;
    }

    public function find(User $owner, string $publicId): ?CashierShift
    {
        return CashierShift::withoutGlobalScopes()->with(['store', 'cashier', 'closedBy', 'cashMovements'])->whereIn('store_id', $this->storeIds($owner, 'all'))->where('public_id', $publicId)->first();
    }

    /** @return array<int,array<string,mixed>> */
    public function storeSummaries(User $owner, array $filters): array
    {
        [$start, $end] = $this->dateRange($filters);
        $stores = $this->storeContext->availableStores($owner);
        $ids = $this->storeIds($owner, $filters['store'] ?? 'all');
        $rows = CashMovement::withoutGlobalScopes()->whereIn('store_id', $ids)->whereBetween('created_at', [$start, $end])->select('store_id', 'type')->selectRaw('COALESCE(SUM(amount),0) amount')->groupBy('store_id', 'type')->get()->groupBy('store_id');
        $actuals = CashierShift::withoutGlobalScopes()->whereIn('store_id', $ids)->whereNotNull('closed_at')->whereBetween('closed_at', [$start, $end])->select('store_id')->selectRaw('COALESCE(SUM(closing_cash_actual),0) actual, COALESCE(SUM(cash_difference),0) variance')->groupBy('store_id')->get()->keyBy('store_id');

        return $stores->whereIn('id', $ids)->map(function (Store $store) use ($rows, $actuals): array {
            $group = $rows->get($store->id, collect())->keyBy(fn ($row): string => $row->type instanceof \BackedEnum ? $row->type->value : (string) $row->type);
            $opening = (int) ($group->get('opening')->amount ?? 0); $sales = (int) ($group->get('sale')->amount ?? 0); $cashIn = (int) ($group->get('cash_in')->amount ?? 0); $refunds = (int) ($group->get('refund')->amount ?? 0); $cashOut = (int) ($group->get('cash_out')->amount ?? 0); $actual = $actuals->get($store->id);
            return ['store' => $store, 'opening' => $opening, 'sales' => $sales, 'cashIn' => $cashIn, 'cashOut' => $cashOut, 'refunds' => $refunds, 'expected' => $this->calculator->expectedFromParts($opening, $sales, $cashIn, $refunds, $cashOut), 'actual' => (int) ($actual->actual ?? 0), 'variance' => (int) ($actual->variance ?? 0)];
        })->values()->all();
    }

    public function activeShifts(User $owner, array $filters): \Illuminate\Support\Collection
    {
        [$start, $end] = $this->dateRange($filters);
        return CashierShift::withoutGlobalScopes()->with(['store', 'cashier'])->whereIn('store_id', $this->storeIds($owner, $filters['store'] ?? 'all'))->where('status', ShiftStatus::Open)->whereBetween('opened_at', [$start, $end])->latest('opened_at')->get();
    }

    /** @return array<int, array{movement:CashMovement,invoice:?string,salePublicId:?string}> */
    public function movements(CashierShift $shift): array
    {
        $movements = $shift->cashMovements()->latest('created_at')->get();
        $paymentIds = $movements->where('reference_type', 'payment')->pluck('reference_id')->filter();
        $payments = $paymentIds->isEmpty() ? collect() : \App\Models\Payment::withoutGlobalScopes()->with('sale')->whereIn('id', $paymentIds)->get()->keyBy('id');
        return $movements->map(fn (CashMovement $movement): array => ['movement' => $movement, 'invoice' => $payments->get($movement->reference_id)?->sale?->invoice_number, 'salePublicId' => $payments->get($movement->reference_id)?->sale?->public_id])->all();
    }

    /** @return array<int,Store> */
    public function stores(User $owner): array { return $this->storeContext->availableStores($owner)->all(); }
    /** @return array<int,int> */
    private function storeIds(User $owner, string $identifier): array { $stores = $this->storeContext->availableStores($owner); if ($identifier !== 'all' && filled($identifier)) $stores = $stores->filter(fn (Store $store): bool => $store->public_id === $identifier); return $stores->pluck('id')->all(); }
    private function customRange(array $filters, CarbonImmutable $today): array { try { $tz = $this->browserTimezone->current(); return [CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateFrom'], $tz)->startOfDay()->utc(), CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateTo'], $tz)->endOfDay()->utc()]; } catch (\Throwable) { return [$today->subDays(29)->startOfDay()->utc(), $today->endOfDay()->utc()]; } }
}
