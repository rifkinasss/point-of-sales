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

class RecordInitialStockAction
{
    public function handle(Product $product, User $performedBy, int $quantity, ?string $reason = null): StockMovement
    {
        if ($product->product_type !== ProductType::Stockable) {
            throw ValidationException::withMessages([
                'product' => 'Produk non-stok tidak memiliki persediaan.',
            ]);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Stok awal harus lebih dari nol.',
            ]);
        }

        return DB::transaction(function () use ($performedBy, $product, $quantity, $reason): StockMovement {
            if ($product->stockMovements()->exists()) {
                throw ValidationException::withMessages([
                    'product' => 'Stok awal sudah pernah dicatat. Gunakan penyesuaian stok untuk koreksi.',
                ]);
            }

            $inventory = Inventory::query()->firstOrCreate(
                ['product_id' => $product->id],
                ['quantity' => 0],
            );

            $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $before = $inventory->quantity;
            $after = $before + $quantity;

            $inventory->update(['quantity' => $after]);

            return StockMovement::query()->create([
                'product_id' => $product->id,
                'type' => StockMovementType::InitialIn,
                'quantity_change' => $quantity,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference_type' => 'initial_stock',
                'reason' => $reason,
                'performed_by' => $performedBy->id,
                'occurred_at' => now(),
            ]);
        });
    }
}
