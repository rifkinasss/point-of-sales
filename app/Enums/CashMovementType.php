<?php

namespace App\Enums;

enum CashMovementType: string
{
    case Opening = 'opening';
    case Sale = 'sale';
    case Refund = 'refund';
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
}
