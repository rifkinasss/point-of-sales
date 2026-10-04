<?php

namespace Tests\Feature;

use App\Actions\Inventory\ReceivePurchaseAction;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_without_items_cannot_be_received(): void
    {
        $store = Store::query()->create(['name' => 'Toko uji', 'slug' => 'toko-uji']);
        $purchase = Purchase::query()->create([
            'store_id' => $store->id,
            'purchase_number' => 'PO-UJI-EMPTY',
            'status' => 'draft',
            'subtotal' => 0,
            'grand_total' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(ReceivePurchaseAction::class)->handle($purchase, User::factory()->create());
    }

    public function test_receiving_purchase_updates_inventory_and_ledger_atomically(): void
    {
        [$user, $purchase, $product] = $this->purchaseContext();
        $purchase->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 5,
            'unit_cost' => 1000,
            'line_total' => 5000,
        ]);

        $received = app(ReceivePurchaseAction::class)->handle($purchase, $user);

        $this->assertSame('received', $received->status);
        $this->assertSame(5, $product->inventory()->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::AdjustmentIn->value,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
            'quantity_change' => 5,
            'quantity_before' => 0,
            'quantity_after' => 5,
        ]);
    }

    public function test_received_purchase_cannot_be_received_twice(): void
    {
        [$user, $purchase, $product] = $this->purchaseContext();
        $purchase->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 5,
            'unit_cost' => 1000,
            'line_total' => 5000,
        ]);

        app(ReceivePurchaseAction::class)->handle($purchase, $user);

        $this->expectException(ValidationException::class);
        app(ReceivePurchaseAction::class)->handle($purchase, $user);
    }

    public function test_invalid_item_rolls_back_all_inventory_changes(): void
    {
        [$user, $purchase, $product] = $this->purchaseContext();
        $purchase->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 5,
            'unit_cost' => 1000,
            'line_total' => 5000,
        ]);
        $purchase->items()->create([
            'product_id' => null,
            'product_name' => 'Produk hilang',
            'quantity' => 1,
            'unit_cost' => 1000,
            'line_total' => 1000,
        ]);

        $this->expectException(ValidationException::class);
        try {
            app(ReceivePurchaseAction::class)->handle($purchase, $user);
        } finally {
            $this->assertSame(0, $product->inventory()->value('quantity') ?? 0);
            $this->assertDatabaseCount('stock_movements', 0);
            $this->assertSame('draft', $purchase->fresh()->status);
        }
    }

    /** @return array{User, Purchase, Product} */
    private function purchaseContext(): array
    {
        $store = Store::query()->create(['name' => 'Toko uji', 'slug' => 'toko-uji']);
        $user = User::factory()->create(['store_id' => $store->id]);
        $supplier = Supplier::query()->create(['store_id' => $store->id, 'name' => 'Supplier uji', 'is_active' => true]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Pcs', 'symbol' => 'pcs', 'is_active' => true]);
        $product = Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => 'Produk pembelian',
            'sku' => 'SKU-PO-001',
            'product_type' => 'stockable',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);
        $purchase = Purchase::query()->create([
            'store_id' => $store->id,
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PO-UJI-001',
            'status' => 'draft',
            'subtotal' => 5000,
            'grand_total' => 5000,
        ]);

        return [$user, $purchase, $product];
    }
}
