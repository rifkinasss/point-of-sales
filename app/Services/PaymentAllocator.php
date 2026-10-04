<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Sale;

class PaymentAllocator
{
    public function summarize(Sale $sale): PaymentSummary
    {
        $payments = $sale->relationLoaded('payments')
            ? $sale->payments
            : $sale->payments()->get();
        $completed = $payments->where('status', PaymentStatus::Completed);
        $totalPaid = (int) $completed->sum('amount_due');

        return new PaymentSummary(
            grandTotal: (int) $sale->grand_total,
            totalPaid: $totalPaid,
            remainingDue: max(0, (int) $sale->grand_total - $totalPaid),
            paymentCount: $completed->count(),
            breakdown: $completed->groupBy(fn ($payment): string => $payment->method->value)
                ->map(fn ($rows): int => (int) $rows->sum('amount_due'))
                ->all(),
        );
    }
}
