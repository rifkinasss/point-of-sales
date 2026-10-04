<?php

namespace App\Enums;

enum StockMovementType: string
{
    case InitialIn = 'initial_in';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case SaleOut = 'sale_out';
    case SaleVoidIn = 'sale_void_in';
    case SaleReturnIn = 'sale_return_in';

    public function isInbound(): bool
    {
        return match ($this) {
            self::InitialIn, self::AdjustmentIn, self::SaleVoidIn, self::SaleReturnIn => true,
            self::AdjustmentOut, self::SaleOut => false,
        };
    }
}
