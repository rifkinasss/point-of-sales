<?php

namespace App\Enums;

enum SaleStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Voided = 'voided';
    case Refunded = 'refunded';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::PendingPayment => $target === self::Paid || $target === self::Voided,
            self::Paid => $target === self::Refunded,
            self::Voided, self::Refunded => false,
        };
    }
}
