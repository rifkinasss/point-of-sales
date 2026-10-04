<?php

namespace App\Actions\Sale;

use App\Enums\ProductType;
use App\Enums\CashMovementType;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\CashMovement;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundSaleAction
{
    public function handle(Sale $sale, User $user, string $reason): SaleReturn
    {
        if (! in_array($user->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true)) {
            throw ValidationException::withMessages(['authorization' => 'Role ini tidak berwenang melakukan refund.']);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'Alasan refund wajib diisi.']);
        }

        return DB::transaction(function () use ($sale, $user, $reason): SaleReturn {
            $sale = Sale::query()->with('items.product', 'payments', 'cashierShift')->lockForUpdate()->findOrFail($sale->id);

            if ($user->is_active === false || ($sale->store_id && $user->store_id && $sale->store_id !== $user->store_id)) {
                throw ValidationException::withMessages(['authorization' => 'Transaksi berasal dari toko lain atau pengguna tidak aktif.']);
            }

            if ($sale->status !== SaleStatus::Paid) {
                throw ValidationException::withMessages(['sale' => 'Hanya transaksi lunas yang dapat direfund.']);
            }

            $movements = StockMovement::query()
                ->where('reference_id', $sale->id)
                ->whereIn('reference_type', ['sale', 'recipe_sale'])
                ->where('type', StockMovementType::SaleOut)
                ->lockForUpdate()
                ->get();
            $expected = $this->expectedSaleConsumption($sale);
            $actual = $movements->groupBy('product_id')->map(fn ($rows): int => abs((int) $rows->sum('quantity_change')));

            foreach ($expected as $productId => $quantity) {
                if ($actual->get($productId, 0) !== $quantity) {
                    throw ValidationException::withMessages(['sale' => 'Mutasi stok penjualan tidak lengkap sehingga refund dibatalkan.']);
                }
            }

            $storeId = $sale->store_id ?? $user->store_id;

            if (! $storeId) {
                throw ValidationException::withMessages(['sale' => 'Refund membutuhkan konteks toko.']);
            }

            $return = SaleReturn::query()->create([
                'store_id' => $storeId,
                'sale_id' => $sale->id,
                'return_number' => 'RTN-'.now()->format('YmdHis'),
                'status' => 'completed',
                'refund_total' => $sale->grand_total,
                'processed_by' => $user->id,
                'reason' => $reason,
                'refunded_at' => now(),
            ]);

            foreach ($sale->items as $item) {
                $hasRecipe = $item->product?->recipeIngredients()->exists() ?? false;
                $restock = $item->product?->product_type === ProductType::Stockable && ! $hasRecipe;
                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'refund_amount' => $item->line_total,
                    'restock' => $restock,
                ]);
            }

            foreach ($movements as $movement) {
                $inventory = Inventory::query()->firstOrCreate(['product_id' => $movement->product_id], ['quantity' => 0]);
                $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
                $before = $inventory->quantity;
                $change = -$movement->quantity_change;
                $after = $before + $change;
                $inventory->update(['quantity' => $after]);

                StockMovement::query()->create([
                    'store_id' => $storeId,
                    'product_id' => $movement->product_id,
                    'type' => StockMovementType::SaleReturnIn,
                    'quantity_change' => $change,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference_type' => 'sale_return',
                    'reference_id' => $return->id,
                    'reason' => 'Refund '.$return->return_number,
                    'performed_by' => $user->id,
                    'occurred_at' => now(),
                ]);
            }

            $completedPayments = $sale->payments->where('status', PaymentStatus::Completed);
            $cashRefund = (int) $completedPayments
                ->filter(fn ($payment): bool => $payment->method->isCash())
                ->sum('amount_due');

            if ($cashRefund > 0) {
                CashMovement::query()->create([
                    'store_id' => $storeId,
                    'shift_id' => $sale->cashierShift?->id,
                    'user_id' => $user->id,
                    'type' => CashMovementType::Refund,
                    'amount' => $cashRefund,
                    'reference_type' => 'sale_return',
                    'reference_id' => $return->id,
                    'note' => 'Refund '.$return->return_number,
                ]);
            }

            $sale->payments()
                ->where('status', PaymentStatus::Completed)
                ->update(['status' => PaymentStatus::Refunded, 'refunded_at' => now()]);

            $sale->transitionTo(SaleStatus::Refunded)->save();
            app(AuditLogger::class)->log('sale_refunded', $sale, [], ['return_number' => $return->return_number, 'reason' => $reason]);

            return $return;
        });
    }

    /** @return array<int, int> */
    private function expectedSaleConsumption(Sale $sale): array
    {
        $expected = [];

        foreach ($sale->items as $item) {
            $product = $item->product;

            if (! $product || ! $product->is_active) {
                continue;
            }

            $ingredients = $product->recipeIngredients()->get();

            if ($ingredients->isNotEmpty()) {
                foreach ($ingredients as $ingredient) {
                    $expected[$ingredient->ingredient_product_id] = ($expected[$ingredient->ingredient_product_id] ?? 0) + ($ingredient->quantity_required * $item->quantity);
                }

                continue;
            }

            if ($product->product_type->tracksInventory()) {
                $expected[$product->id] = ($expected[$product->id] ?? 0) + $item->quantity;
            }
        }

        return $expected;
    }
}
