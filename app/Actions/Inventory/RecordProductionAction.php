<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordProductionAction
{
    public function handle(Product $product, int $quantity, User $user, ?string $note = null): ProductionBatch
    {
        if ($quantity < 1 || $product->recipeIngredients()->count() === 0) throw ValidationException::withMessages(['product' => 'Pilih produk dengan resep aktif dan jumlah produksi valid.']);
        return DB::transaction(function () use ($note, $product, $quantity, $user): ProductionBatch {
            $product = Product::query()->with('recipeIngredients.ingredient')->lockForUpdate()->findOrFail($product->id);
            foreach ($product->recipeIngredients as $ingredientLine) {
                $ingredient = $ingredientLine->ingredient; $required = $ingredientLine->quantity_required * $quantity;
                $inventory = $ingredient ? Inventory::query()->where('product_id', $ingredient->id)->lockForUpdate()->first() : null;
                if (! $inventory || $inventory->quantity < $required) throw ValidationException::withMessages(['stock' => "Bahan baku {$ingredient?->name} tidak mencukupi."]);
                $before = $inventory->quantity; $inventory->update(['quantity' => $before - $required]);
                $ingredient->stockMovements()->create(['type' => StockMovementType::AdjustmentOut, 'quantity_change' => -$required, 'quantity_before' => $before, 'quantity_after' => $before - $required, 'reference_type' => 'production', 'reason' => 'Bahan produksi '.$product->name, 'performed_by' => $user->id, 'occurred_at' => now()]);
            }
            $batch = ProductionBatch::query()->create(['number' => 'PRD-'.now()->format('YmdHis'), 'product_id' => $product->id, 'quantity_produced' => $quantity, 'produced_by' => $user->id, 'produced_at' => now(), 'note' => $note]);
            $output = Inventory::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0]); $output = Inventory::query()->lockForUpdate()->findOrFail($output->id); $before = $output->quantity;
            $output->update(['quantity' => $before + $quantity]);
            $product->stockMovements()->create(['type' => StockMovementType::AdjustmentIn, 'quantity_change' => $quantity, 'quantity_before' => $before, 'quantity_after' => $before + $quantity, 'reference_type' => 'production', 'reference_id' => $batch->id, 'reason' => 'Hasil produksi '.$batch->number, 'performed_by' => $user->id, 'occurred_at' => now()]);
            app(AuditLogger::class)->log('production_recorded', $batch);
            return $batch;
        });
    }
}
