<?php

namespace App\Services;

final readonly class SaleCalculation
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function __construct(
        public array $items,
        public int $subtotal,
        public int $itemDiscountTotal,
        public int $promotionDiscountTotal,
        public int $transactionDiscountTotal,
        public int $taxTotal,
        public int $grandTotal,
    ) {}
}
