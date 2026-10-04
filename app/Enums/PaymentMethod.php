<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Qris = 'qris';
    case EWallet = 'e_wallet';

    public function isCash(): bool
    {
        return $this === self::Cash;
    }
}
