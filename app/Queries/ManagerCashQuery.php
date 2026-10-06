<?php

namespace App\Queries;

use App\Models\CashMovement;
use App\Models\CashierShift;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use App\Services\ActiveStoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ManagerCashQuery
{
    public function __construct(private readonly ManagerShiftQuery $shifts) {}

    public function summary(User $manager, array $filters): array
    {
        [$start, $end] = $this->shifts->dateRange($manager, $filters);
        $movements = CashMovement::withoutGlobalScopes()
            ->whereIn('store_id', $this->storeIds($manager))
            ->whereBetween('created_at', [$start, $end])
            ->select('type', DB::raw('SUM(amount) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $closed = CashierShift::query()->withoutGlobalScopes()
            ->whereIn('store_id', $this->storeIds($manager))
            ->whereNotNull('closed_at')
            ->whereBetween('closed_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(closing_cash_actual), 0) as actual, COALESCE(SUM(cash_difference), 0) as variance')
            ->first();

        $calculator = app(CashBalanceCalculator::class);

        return [
            'opening' => (int) ($movements->get('opening') ?? 0),
            'sales' => (int) ($movements->get('sale') ?? 0),
            'cashIn' => (int) ($movements->get('cash_in') ?? 0),
            'refunds' => (int) ($movements->get('refund') ?? 0),
            'cashOut' => (int) ($movements->get('cash_out') ?? 0),
            'expected' => $calculator->expectedFromParts((int) ($movements->get('opening') ?? 0), (int) ($movements->get('sale') ?? 0), (int) ($movements->get('cash_in') ?? 0), (int) ($movements->get('refund') ?? 0), (int) ($movements->get('cash_out') ?? 0)),
            'actual' => (int) ($closed?->actual ?? 0),
            'variance' => (int) ($closed?->variance ?? 0),
        ];
    }

    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        $paginator = $this->shifts->paginate($manager, $filters + ['perPage' => 20]);
        $summaries = app(CashBalanceCalculator::class)->summaries(collect($paginator->items()));

        foreach ($paginator->getCollection() as $shift) {
            $shift->setAttribute('cash_summary', $summaries->get($shift->id));
        }

        return $paginator;
    }

    /** @return array<int, int> */
    private function storeIds(User $manager): array
    {
        return app(ActiveStoreContext::class)->scope($manager)->ids();
    }
}
