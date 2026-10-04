<?php

namespace App\Actions\Sale;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Furic\FilamentRedeemCodes\Actions\RedeemCodeAction;
use Furic\FilamentRedeemCodes\Exceptions\RedeemException;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcessPaymentAction
{
    public function handle(Sale $sale, User $cashier, PaymentMethod $method, int $amountReceived, ?string $referenceNumber = null): Sale
    {
        return DB::transaction(function () use ($amountReceived, $cashier, $method, $referenceNumber, $sale): Sale {
            $sale = Sale::query()->with('customer', 'items.product')->lockForUpdate()->findOrFail($sale->id);
            $shift = $sale->cashierShift()->lockForUpdate()->firstOrFail();

            if ($sale->status !== SaleStatus::PendingPayment || $shift->status !== ShiftStatus::Open) {
                throw ValidationException::withMessages([
                    'sale' => 'Hanya transaksi pending pada shift aktif yang dapat dibayar.',
                ]);
            }

            if ($sale->cashier_id !== $cashier->id || ($sale->store_id && $shift->store_id && $sale->store_id !== $shift->store_id) || ($cashier->store_id && $shift->store_id !== $cashier->store_id)) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi hanya dapat diselesaikan oleh kasir dan toko pemilik shift.',
                ]);
            }

            if ($method->isCash()) {
                if ($amountReceived < $sale->grand_total) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Uang diterima kurang dari total transaksi.',
                    ]);
                }

                $changeAmount = $amountReceived - $sale->grand_total;
            } else {
                if ($amountReceived !== $sale->grand_total) {
                    throw ValidationException::withMessages([
                        'amount_received' => 'Pembayaran non-tunai harus sama dengan total transaksi.',
                    ]);
                }

                $changeAmount = 0;
            }

            if ($sale->redeem_code_id) {
                $code = RedeemCode::query()->lockForUpdate()->find($sale->redeem_code_id);

                if (! $code) {
                    throw ValidationException::withMessages(['promotion' => 'Kode promosi tidak lagi tersedia.']);
                }

                try {
                    app(RedeemCodeAction::class)->execute($code->code, request()->ip(), request()->userAgent());
                } catch (RedeemException $exception) {
                    throw ValidationException::withMessages(['promotion' => 'Kode promosi tidak dapat digunakan: '.$exception->getMessage()]);
                }
            }

            foreach ($sale->items as $item) {
                $product = $item->product;

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'product' => "Produk {$item->product_name} tidak aktif atau tidak tersedia.",
                    ]);
                }

                $recipeIngredients = $product->recipeIngredients()->with('ingredient')->get();

                if ($recipeIngredients->isNotEmpty()) {
                    foreach ($recipeIngredients as $recipeIngredient) {
                        $ingredient = $recipeIngredient->ingredient;
                        $requiredQuantity = $recipeIngredient->quantity_required * $item->quantity;
                        $inventory = $ingredient ? Inventory::query()->where('product_id', $ingredient->id)->lockForUpdate()->first() : null;

                        if (! $ingredient || ! $inventory || $inventory->quantity < $requiredQuantity) {
                            throw ValidationException::withMessages(['stock' => "Bahan baku {$ingredient?->name} tidak mencukupi untuk {$item->product_name}."]);
                        }

                        $before = $inventory->quantity;
                        $after = $before - $requiredQuantity;
                        $inventory->update(['quantity' => $after]);
                        $ingredient->stockMovements()->create(['type' => StockMovementType::SaleOut, 'quantity_change' => -$requiredQuantity, 'quantity_before' => $before, 'quantity_after' => $after, 'reference_type' => 'recipe_sale', 'reference_id' => $sale->id, 'performed_by' => $cashier->id, 'occurred_at' => now()]);
                    }

                    continue;
                }

                if (! $product->product_type->tracksInventory()) {
                    continue;
                }

                $inventory = Inventory::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory || $inventory->quantity < $item->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok {$item->product_name} tidak mencukupi.",
                    ]);
                }

                $before = $inventory->quantity;
                $after = $before - $item->quantity;

                $inventory->update(['quantity' => $after]);

                $product->stockMovements()->create([
                    'type' => StockMovementType::SaleOut,
                    'quantity_change' => -$item->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'performed_by' => $cashier->id,
                    'occurred_at' => now(),
                ]);
            }

            Payment::query()->create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount_due' => $sale->grand_total,
                'amount_received' => $amountReceived,
                'change_amount' => $changeAmount,
                'reference_number' => $referenceNumber,
                'paid_at' => now(),
                'received_by' => $cashier->id,
            ]);

            $sale->transitionTo(SaleStatus::Paid);
            $sale->forceFill(['completed_at' => now()])->save();

            if ($sale->customer) {
                $sale->customer->increment('points', intdiv($sale->grand_total, 10_000));
            }

            return $sale->refresh()->load('items', 'payment');
        });
    }
}
