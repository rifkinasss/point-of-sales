<?php

namespace App\Services;

final readonly class PaymentSummary
{
    /** @param array<string, int> $breakdown */
    public function __construct(
        public int $grandTotal,
        public int $totalPaid,
        public int $remainingDue,
        public int $paymentCount,
        public array $breakdown,
    ) {}
}
