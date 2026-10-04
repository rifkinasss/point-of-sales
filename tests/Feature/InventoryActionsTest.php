<?php

namespace Tests\Feature;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\RecordInitialStockAction;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_stock_creates_inventory_and_an_immutable_movement(): void
    {
        $user = User::factory()->create();
        $product = $this->makeStockableProduct();

        $movement = app(RecordInitialStockAction::class)->handle($product, $user, 12, 'Stok awal');

        $this->assertSame(12, $product->inventory()->value('quantity'));
        $this->assertSame(StockMovementType::InitialIn, $movement->type);
        $this->assertSame(0, $movement->quantity_before);
        $this->assertSame(12, $movement->quantity_after);
    }

    public function test_adjustment_out_cannot_make_stock_negative(): void
    {
        $user = User::factory()->create();
        $product = $this->makeStockableProduct();

        app(RecordInitialStockAction::class)->handle($product, $user, 5);

        $this->expectException(ValidationException::class);

        app(AdjustStockAction::class)->handle(
            $product,
            $user,
            StockMovementType::AdjustmentOut,
            6,
            'Barang rusak',
        );
    }

    private function makeStockableProduct(): Product
    {
        $unit = Unit::query()->create([
            'name' => 'Pcs',
            'symbol' => 'pcs',
            'is_active' => true,
        ]);

        return Product::query()->create([
            'unit_id' => $unit->id,
            'name' => 'Produk uji',
            'sku' => 'SKU-UJI-001',
            'product_type' => 'stockable',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);
    }
}
