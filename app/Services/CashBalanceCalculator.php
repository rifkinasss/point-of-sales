<?php

namespace App\Services;

use App\Enums\CashMovementType;
use App\Models\CashMovement;
use App\Models\CashierShift;

class CashBalanceCalculator
{
    /** @return array{opening: int, sales: int, cashIn: int, refunds: int, cashOut: int, expected: int} */
    public function summary(CashierShift $shift): array
    {
        $movements = CashMovement::withoutGlobalScopes()
            ->where('shift_id', $shift->id)
            ->get()
            ->groupBy(fn (CashMovement $movement): string => $movement->type->value);

        $opening = (int) ($movements->get(CashMovementType::Opening->value)?->sum('amount') ?? 0);
        $sales = (int) ($movements->get(CashMovementType::Sale->value)?->sum('amount') ?? 0);
        $cashIn = (int) ($movements->get(CashMovementType::CashIn->value)?->sum('amount') ?? 0);
        $refunds = (int) ($movements->get(CashMovementType::Refund->value)?->sum('amount') ?? 0);
        $cashOut = (int) ($movements->get(CashMovementType::CashOut->value)?->sum('amount') ?? 0);

        return [
            'opening' => $opening,
            'sales' => $sales,
            'cashIn' => $cashIn,
            'refunds' => $refunds,
            'cashOut' => $cashOut,
            'expected' => $opening + $sales + $cashIn - $refunds - $cashOut,
        ];
    }
}
