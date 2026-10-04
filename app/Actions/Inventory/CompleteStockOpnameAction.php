<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteStockOpnameAction
{
    /** @param array<int, array{product_id:int, actual_quantity:int}> $counts */
    public function handle(User $user, array $counts, ?string $note = null): StockOpname
    {
        if ($counts === []) throw ValidationException::withMessages(['counts' => 'Tambahkan minimal satu produk untuk opname.']);

        return DB::transaction(function () use ($counts, $note, $user): StockOpname {
            $opname = StockOpname::query()->create(['number' => 'OPN-'.now()->format('YmdHis'), 'status' => 'completed', 'counted_by' => $user->id, 'counted_at' => now(), 'note' => $note]);
            foreach ($counts as $count) {
                if (! isset($count['product_id'], $count['actual_quantity']) || $count['actual_quantity'] < 0) continue;
                $product = Product::query()->findOrFail($count['product_id']);
                $inventory = Inventory::query()->firstOrCreate(['product_id' => $product->id], ['quantity' => 0]);
                $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
                $before = $inventory->quantity; $actual = (int) $count['actual_quantity']; $difference = $actual - $before;
                StockOpnameItem::query()->create(['stock_opname_id' => $opname->id, 'product_id' => $product->id, 'system_quantity' => $before, 'actual_quantity' => $actual, 'difference' => $difference]);
                if ($difference === 0) continue;
                $inventory->update(['quantity' => $actual]);
                $product->stockMovements()->create(['type' => $difference > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut, 'quantity_change' => $difference, 'quantity_before' => $before, 'quantity_after' => $actual, 'reference_type' => 'stock_opname', 'reference_id' => $opname->id, 'reason' => 'Opname '.$opname->number, 'performed_by' => $user->id, 'occurred_at' => now()]);
            }
            app(AuditLogger::class)->log('stock_opname_completed', $opname, [], ['items' => count($counts)]);
            return $opname;
        });
    }
}
