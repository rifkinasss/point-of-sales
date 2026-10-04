<?php

namespace App\Actions\Sale;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\InvoiceNumberGenerator;
use App\Services\SaleCalculator;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePendingSaleAction
{
    /**
     * @param  array<int, array{product_id: int, quantity: int, discount_amount?: int, modifier_value_ids?: array<int, int>}>  $items
     */
    public function handle(CashierShift $shift, User $cashier, array $items, int $transactionDiscount = 0, ?string $note = null, ?int $redeemCodeId = null, ?string $promotionCode = null, ?int $customerId = null): Sale
    {
        return DB::transaction(function () use ($cashier, $items, $note, $shift, $transactionDiscount, $redeemCodeId, $promotionCode, $customerId): Sale {
            $shift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            if ($shift->status !== ShiftStatus::Open || $shift->user_id !== $cashier->id) {
                throw ValidationException::withMessages(['shift' => 'Kasir harus memiliki shift aktif untuk membuat transaksi.']);
            }

            $customer = $customerId
                ? Customer::query()->where('store_id', $shift->store_id)->where('is_active', true)->find($customerId)
                : null;
            $redeemCode = $redeemCodeId
                ? RedeemCode::query()->with('campaign.rewards')->find($redeemCodeId)
                : null;

            if ($customerId && ! $customer) {
                throw ValidationException::withMessages(['customer' => 'Pelanggan tidak tersedia untuk toko ini.']);
            }
            if ($redeemCodeId && ! $redeemCode) {
                throw ValidationException::withMessages(['promotion' => 'Kode promosi tidak tersedia.']);
            }
            if ($redeemCode && filled($promotionCode) && strtoupper($promotionCode) !== strtoupper($redeemCode->code)) {
                throw ValidationException::withMessages(['promotion' => 'Kode promosi tidak valid.']);
            }

            $store = Store::query()->findOrFail($shift->store_id ?: $cashier->store_id);
            $calculation = app(SaleCalculator::class)->calculate($items, $transactionDiscount, $redeemCode, $store->id, $customer?->tier);
            $sale = Sale::query()->create([
                'store_id' => $store->id,
                'invoice_number' => app(InvoiceNumberGenerator::class)->generate($store),
                'cashier_shift_id' => $shift->id,
                'cashier_id' => $cashier->id,
                'customer_id' => $customer?->id,
                'redeem_code_id' => $redeemCode?->id,
                'promotion_code' => $redeemCode?->code,
                'promotion_discount_total' => $calculation->promotionDiscountTotal,
                'status' => SaleStatus::PendingPayment,
                'subtotal' => $calculation->subtotal,
                'item_discount_total' => $calculation->itemDiscountTotal,
                'transaction_discount_total' => $calculation->transactionDiscountTotal,
                'grand_total' => $calculation->grandTotal,
                'note' => $note,
            ]);

            foreach ($calculation->items as $item) {
                $sale->items()->create($item);
            }

            return $sale->load('items');
        });
    }
}
