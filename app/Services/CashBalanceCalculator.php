<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashierShift;
use Illuminate\Support\Collection;

class CashBalanceCalculator
{
    /** @return array{opening: int, sales: int, cashIn: int, refunds: int, cashOut: int, expected: int} */
    public function summary(CashierShift $shift): array
    {
        return $this->summarizeMovements(CashMovement::withoutGlobalScopes()->where('shift_id', $shift->id)->get());
    }

    /** @param Collection<int, CashierShift> $shifts
     *  @return Collection<int, array{opening:int,sales:int,cashIn:int,refunds:int,cashOut:int,expected:int}>
     */
    public function summaries(Collection $shifts): Collection
    {
        if ($shifts->isEmpty()) {
            return collect();
        }

        $movements = CashMovement::withoutGlobalScopes()
            ->whereIn('shift_id', $shifts->pluck('id'))
            ->get()
            ->groupBy('shift_id');

        return $shifts->mapWithKeys(fn (CashierShift $shift): array => [
            $shift->id => $this->summarizeMovements($movements->get($shift->id, collect())),
        ]);
    }

    /** @param Collection<int, CashMovement> $movements */
    private function summarizeMovements(Collection $movements): array
    {
        $grouped = $movements->groupBy(fn (CashMovement $movement): string => $movement->type->value);

        $opening = (int) ($grouped->get(CashMovementType::Opening->value)?->sum('amount') ?? 0);
        $sales = (int) ($grouped->get(CashMovementType::Sale->value)?->sum('amount') ?? 0);
        $cashIn = (int) ($grouped->get(CashMovementType::CashIn->value)?->sum('amount') ?? 0);
        $refunds = (int) ($grouped->get(CashMovementType::Refund->value)?->sum('amount') ?? 0);
        $cashOut = (int) ($grouped->get(CashMovementType::CashOut->value)?->sum('amount') ?? 0);

        return [
            'opening' => $opening,
            'sales' => $sales,
            'cashIn' => $cashIn,
            'refunds' => $refunds,
            'cashOut' => $cashOut,
            'expected' => $this->expectedFromParts($opening, $sales, $cashIn, $refunds, $cashOut),
        ];
    }

    public function expectedFromParts(int $opening, int $sales, int $cashIn, int $refunds, int $cashOut): int
    {
        return $opening + $sales + $cashIn - $refunds - $cashOut;
    }
}
