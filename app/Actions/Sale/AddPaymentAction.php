<?php

namespace App\Actions\Sale;

use App\Enums\CashMovementType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashMovement;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentAllocator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddPaymentAction
{
    public function handle(
        Sale $sale,
        User $cashier,
        PaymentMethod $method,
        int $amount,
        ?int $amountReceived = null,
        ?string $referenceNumber = null,
    ): Sale {
        if ($amount < 1) {
            throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari nol.']);
        }

        return DB::transaction(function () use ($amount, $amountReceived, $cashier, $method, $referenceNumber, $sale): Sale {
            $sale = Sale::query()->with('payments', 'customer', 'items.product')->lockForUpdate()->findOrFail($sale->id);
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($sale->cashier_shift_id);

            if ($sale->status !== SaleStatus::PendingPayment || $shift->status !== ShiftStatus::Open) {
                throw ValidationException::withMessages(['sale' => 'Hanya transaksi pending pada shift aktif yang dapat dibayar.']);
            }
            if ($sale->cashier_id !== $cashier->id || ($sale->store_id && $shift->store_id && $sale->store_id !== $shift->store_id) || ($cashier->store_id && $shift->store_id !== $cashier->store_id)) {
                throw ValidationException::withMessages(['sale' => 'Transaksi hanya dapat dibayar oleh kasir dan toko pemilik shift.']);
            }

            $summary = app(PaymentAllocator::class)->summarize($sale);
            if ($amount > $summary->remainingDue) {
                throw ValidationException::withMessages(['amount' => 'Nominal melebihi sisa pembayaran.']);
            }

            $received = $amountReceived ?? $amount;
            if ($method->isCash()) {
                if ($received < $amount) {
                    throw ValidationException::withMessages(['amount_received' => 'Uang diterima kurang dari alokasi pembayaran.']);
                }
                $change = $received - $amount;
            } else {
                if ($received !== $amount) {
                    throw ValidationException::withMessages(['amount_received' => 'Pembayaran non-tunai harus sama dengan nominal alokasi.']);
                }
                $change = 0;
            }

            $payment = $sale->payments()->create([
                'method' => $method,
                'status' => PaymentStatus::Completed,
                'amount_due' => $amount,
                'amount_received' => $received,
                'change_amount' => $change,
                'reference_number' => $referenceNumber,
                'paid_at' => now(),
                'received_by' => $cashier->id,
            ]);

            if ($method->isCash()) {
                CashMovement::query()->create([
                    'store_id' => $shift->store_id,
                    'shift_id' => $shift->id,
                    'user_id' => $cashier->id,
                    'type' => CashMovementType::Sale,
                    'amount' => $amount,
                    'reference_type' => 'payment',
                    'reference_id' => $payment->id,
                    'note' => 'Penjualan '.$sale->invoice_number,
                ]);
            }

            $sale->load('payments');
            if (app(PaymentAllocator::class)->summarize($sale)->remainingDue === 0) {
                return app(CompleteSalePaymentAction::class)->handle($sale, $cashier);
            }

            return $sale->refresh()->load('items', 'payments');
        });
    }
}
