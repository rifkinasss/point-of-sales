<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ManagerTransactionQuery
{
    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        [$start, $end] = $this->dateRange($manager, $filters);
        $query = Sale::query()->withoutGlobalScopes()
            ->with(['cashier', 'customer', 'payments'])
            ->where('store_id', $manager->store_id)
            ->whereBetween('created_at', [$start, $end]);

        if (filled($filters['search'] ?? null)) {
            $search = trim((string) $filters['search']);
            $query->where(function ($query) use ($search): void {
                $needle = '%'.mb_strtolower($search).'%';
                $query->whereRaw('LOWER(invoice_number) LIKE ?', [$needle])
                    ->orWhereHas('customer', fn ($customer) => $customer->whereRaw('LOWER(name) LIKE ?', [$needle]))
                    ->orWhereHas('cashier', fn ($cashier) => $cashier->whereRaw('LOWER(name) LIKE ?', [$needle]));
            });
        }

        if (filled($filters['status'] ?? null) && in_array($filters['status'], array_column(SaleStatus::cases(), 'value'), true)) {
            $query->where('status', $filters['status']);
        }

        if (filled($filters['paymentMethod'] ?? null)) {
            $query->whereHas('payments', fn ($payment) => $payment->where('method', $filters['paymentMethod']));
        }

        return $query->latest('created_at')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $manager, string $publicId): ?Sale
    {
        return Sale::query()->withoutGlobalScopes()
            ->with(['items', 'payments', 'cashier', 'customer', 'store', 'cashierShift'])
            ->where('store_id', $manager->store_id)
            ->where('public_id', $publicId)
            ->first();
    }

    /** @return array{CarbonImmutable, CarbonImmutable} */
    private function dateRange(User $manager, array $filters): array
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
                // Invalid custom dates fall back to today; component validation still reports the input.
            }
        }

        return [$today->startOfDay()->utc(), $today->endOfDay()->utc()];
    }
}
