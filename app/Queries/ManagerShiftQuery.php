<?php

namespace App\Queries;

use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ManagerShiftQuery
{
    public function __construct(private readonly CashBalanceCalculator $calculator) {}

    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        [$start, $end] = $this->dateRange($manager, $filters);
        $query = CashierShift::query()->withoutGlobalScopes()
            ->with(['cashier', 'closedBy'])
            ->where('store_id', $manager->store_id)
            ->whereBetween('opened_at', [$start, $end]);

        if (filled($filters['status'] ?? null) && in_array($filters['status'], array_column(ShiftStatus::cases(), 'value'), true)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['cashierId'] ?? null)) {
            $query->where('user_id', (int) $filters['cashierId']);
        }

        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->whereHas('cashier', fn ($cashier) => $cashier->whereRaw('LOWER(name) LIKE ?', [$needle]));
        }

        if (($filters['variance'] ?? '') === 'nonzero') {
            $query->whereNotNull('cash_difference')->where('cash_difference', '!=', 0);
        } elseif (($filters['variance'] ?? '') === 'matching') {
            $query->whereNotNull('cash_difference')->where('cash_difference', 0);
        }

        return $query->latest('opened_at')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $manager, string $publicId): ?CashierShift
    {
        return CashierShift::query()->withoutGlobalScopes()
            ->with(['cashier', 'closedBy', 'store', 'cashMovements'])
            ->where('store_id', $manager->store_id)
            ->where('public_id', $publicId)
            ->first();
    }

    /** @return array{active:int,closed:int,expected:int,variance:int} */
    public function metrics(User $manager, array $filters): array
    {
        [$start, $end] = $this->dateRange($manager, $filters);
        $base = CashierShift::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->whereBetween('opened_at', [$start, $end]);
        $movements = CashMovement::withoutGlobalScopes()->where('store_id', $manager->store_id)->whereBetween('created_at', [$start, $end])->select('type', DB::raw('SUM(amount) as total'))->groupBy('type')->pluck('total', 'type');

        return [
            'active' => (clone $base)->where('status', ShiftStatus::Open)->count(),
            'closed' => (clone $base)->where('status', ShiftStatus::Closed)->count(),
            'expected' => $this->calculator->expectedFromParts((int) ($movements->get('opening') ?? 0), (int) ($movements->get('sale') ?? 0), (int) ($movements->get('cash_in') ?? 0), (int) ($movements->get('refund') ?? 0), (int) ($movements->get('cash_out') ?? 0)),
            'variance' => (int) ((clone $base)->whereNotNull('cash_difference')->sum('cash_difference')),
        ];
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    public function dateRange(User $manager, array $filters): array
    {
        $timezone = $manager->store?->timezone ?: config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($timezone);
        $preset = $filters['datePreset'] ?? 'today';

        if ($preset === 'yesterday') {
            $day = $today->subDay();

            return [$day->startOfDay()->utc(), $day->endOfDay()->utc()];
        }

        if ($preset === 'last_7_days') {
            return [$today->subDays(6)->startOfDay()->utc(), $today->endOfDay()->utc()];
        }

        if ($preset === 'custom' && filled($filters['dateFrom'] ?? null) && filled($filters['dateTo'] ?? null)) {
            try {
                return [
                    CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateFrom'], $timezone)->startOfDay()->utc(),
                    CarbonImmutable::createFromFormat('Y-m-d', (string) $filters['dateTo'], $timezone)->endOfDay()->utc(),
                ];
            } catch (\Throwable) {
                // Component validation reports invalid custom dates; today is a safe query fallback.
            }
        }

        return [$today->startOfDay()->utc(), $today->endOfDay()->utc()];
    }
}
