<?php

namespace App\Actions\Inventory;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStockAction
{
    public function handle(Product $product, User $performedBy, StockMovementType $type, int $quantity, string $reason): StockMovement
    {
        if ($product->product_type !== ProductType::Stockable) {
            throw ValidationException::withMessages([
                'product' => 'Produk non-stok tidak memiliki persediaan.',
            ]);
        }

        if (! in_array($type, [StockMovementType::AdjustmentIn, StockMovementType::AdjustmentOut], true)) {
            throw ValidationException::withMessages([
                'type' => 'Tipe mutasi tidak valid untuk penyesuaian stok.',
            ]);
        }

        if ($quantity < 1 || blank($reason)) {
            throw ValidationException::withMessages([
                'quantity' => 'Kuantitas harus lebih dari nol dan alasan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($performedBy, $product, $quantity, $reason, $type): StockMovement {
            $inventory = Inventory::query()->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0],
            );

            $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $before = $inventory->quantity;
            $change = $type->isInbound() ? $quantity : -$quantity;
            $after = $before + $change;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak cukup untuk penyesuaian keluar.',
                ]);
            }

            $inventory->update(['quantity' => $after]);

            return StockMovement::query()->create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity_change' => $change,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'stock_adjustment',
                'reason' => $reason,
                'performed_by' => $performedBy->id,
                'occurred_at' => now(),
            ]);
        });
    }
}
