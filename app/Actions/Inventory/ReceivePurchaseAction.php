<?php

namespace App\Actions\Inventory;

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivePurchaseAction
{
    public function handle(Purchase $purchase, User $user): Purchase
    {
        return DB::transaction(function () use ($purchase, $user): Purchase {
            $purchase = Purchase::query()->with('items.product')->lockForUpdate()->findOrFail($purchase->id);
            if ($purchase->status === 'received') throw ValidationException::withMessages(['purchase' => 'Pembelian ini sudah diterima.']);
            if ($purchase->items->isEmpty()) throw ValidationException::withMessages(['purchase' => 'Pembelian harus memiliki minimal satu item sebelum diterima.']);
            foreach ($purchase->items as $item) {
                if (! $item->product || $item->quantity < 1) {
                    throw ValidationException::withMessages(['purchase' => 'Setiap item pembelian harus memiliki produk dan kuantitas lebih dari nol.']);
                }
            }
            foreach ($purchase->items as $item) {
                if (! $item->product) continue;
                $inventory = Inventory::query()->firstOrCreate(['product_id' => $item->product_id], ['quantity' => 0]);
                $inventory = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
                $before = $inventory->quantity; $after = $before + $item->quantity;
                $inventory->update(['quantity' => $after]);
                $item->product->stockMovements()->create(['type' => StockMovementType::AdjustmentIn, 'quantity_change' => $item->quantity, 'quantity_before' => $before, 'quantity_after' => $after, 'reference_type' => 'purchase', 'reference_id' => $purchase->id, 'reason' => 'Penerimaan '.$purchase->purchase_number, 'performed_by' => $user->id, 'occurred_at' => now()]);
            }
            $purchase->update(['status' => 'received', 'received_at' => now(), 'received_by' => $user->id]);
            app(AuditLogger::class)->log('purchase_received', $purchase);
            return $purchase;
        });
    }
}
