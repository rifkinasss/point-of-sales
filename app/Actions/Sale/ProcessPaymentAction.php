<?php

namespace App\Actions\Sale;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentAllocator;

class ProcessPaymentAction
{
    public function handle(Sale $sale, User $cashier, PaymentMethod $method, int $amountReceived, ?string $referenceNumber = null): Sale
    {
        $remainingDue = app(PaymentAllocator::class)->summarize($sale)->remainingDue;

        return app(AddPaymentAction::class)->handle(
            $sale,
            $cashier,
            $method,
            $remainingDue,
            $amountReceived,
            $referenceNumber,
        );
    }
}
