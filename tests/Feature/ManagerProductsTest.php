<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_search_filter_and_open_store_scoped_product_detail(): void
    {
        [$manager, $cashier, $store] = $this->context('m4-products');
        $category = Category::query()->create(['store_id' => $store->id, 'name' => 'Minuman', 'is_active' => true]);
        $product = $this->product($store, $category, 'Es Teh Manis', 'DRK-001', '899001', 5);
        Inventory::query()->create(['product_id' => $product->id, 'quantity' => 2]);
        $other = $this->context('m4-other');
        $otherProduct = $this->product($other[2], null, 'Rahasia Store B', 'B-001', null, 0);

        $this->actingAs($manager)->get(route('manager.products.index'))->assertOk()->assertSee('Monitoring katalog dan inventori')->assertSee($product->name)->assertDontSee($otherProduct->name);
        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Products\Index::class)
            ->set('search', '899001')->assertSee($product->name)
            ->set('category', $category->public_id)->assertSee($product->name)
            ->set('stockStatus', 'low')->assertSee('Menipis');
        $this->actingAs($manager)->get(route('manager.products.show', ['product' => $product->public_id]))->assertOk()->assertSee('Riwayat pergerakan stok');
        $this->actingAs($manager)->get('/manager/products/'.$product->id)->assertNotFound();
        $this->actingAs($manager)->get(route('manager.products.show', ['product' => $otherProduct->public_id]))->assertNotFound();
        $this->actingAs($cashier)->get(route('manager.products.index'))->assertForbidden();
    }

    public function test_manager_stock_adjustment_is_audited_and_cannot_go_below_zero(): void
    {
        [$manager, , $store] = $this->context('m4-adjustment');
        $product = $this->product($store, null, 'Kopi', 'DRK-002', null, 2);
        Inventory::query()->create(['product_id' => $product->id, 'quantity' => 3]);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Products\Show::class, ['product' => $product->public_id])
            ->call('openAdjustment')->set('adjustmentDirection', 'in')->set('adjustmentQuantity', '4')->set('adjustmentReason', 'Restock manual')->call('saveAdjustment');
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => StockMovementType::AdjustmentIn->value, 'quantity_change' => 4, 'performed_by' => $manager->id, 'store_id' => $store->id]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 7]);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Products\Show::class, ['product' => $product->public_id])
            ->call('openAdjustment')->set('adjustmentDirection', 'out')->set('adjustmentQuantity', '8')->set('adjustmentReason', 'Koreksi')->call('saveAdjustment')->assertHasErrors('adjustmentQuantity');
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id, 'quantity_change' => -8]);
    }

    public function test_adjustment_reason_is_required_and_dashboard_links_to_low_stock(): void
    {
        [$manager, , $store] = $this->context('m4-dashboard');
        $product = $this->product($store, null, 'Gula', 'ING-001', null, 3);
        Inventory::query()->create(['product_id' => $product->id, 'quantity' => 1]);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Products\Show::class, ['product' => $product->public_id])
            ->call('openAdjustment')->set('adjustmentQuantity', '1')->call('saveAdjustment')->assertHasErrors('adjustmentReason');
        $this->actingAs($manager)->get('/manager')->assertOk()->assertSee(route('manager.products.index', ['stock_status' => 'low']), false);
    }

    /** @return array{User, User, Store} */
    private function context(string $slug): array
    {
        $store = Store::query()->create(['name' => 'Store '.$slug, 'slug' => $slug]);
        return [
            User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager, 'is_active' => true]),
            User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier, 'is_active' => true]),
            $store,
        ];
    }

    private function product(Store $store, ?Category $category, string $name, string $sku, ?string $barcode, int $minimum): Product
    {
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Unit '.$sku, 'symbol' => 'u-'.$sku, 'is_active' => true]);
        return Product::query()->create(['store_id' => $store->id, 'category_id' => $category?->id, 'unit_id' => $unit->id, 'name' => $name, 'sku' => $sku, 'barcode' => $barcode, 'product_type' => ProductType::Stockable, 'selling_price' => 7000, 'cost_price' => 3000, 'minimum_stock' => $minimum, 'is_active' => true]);
    }
}
