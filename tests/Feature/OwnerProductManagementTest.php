<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use App\Livewire\Owner\Categories\Index as CategoryIndex;
use App\Livewire\Owner\Products\Form as ProductForm;
use App\Livewire\Owner\Products\Show as ProductShow;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_product_workspace_is_store_scoped_and_uses_public_product_identifiers(): void
    {
        [$owner, $store, $outside] = $this->context();
        $product = $this->product($store, 'Nasi Goreng', 'NAS-001');
        $outsideProduct = $this->product($outside, 'Produk Rahasia', 'OUT-001');

        $this->actingAs($owner)->get(route('owner.products'))->assertOk()->assertSee($product->name)->assertDontSee($outsideProduct->name);
        $this->actingAs($owner)->get(route('owner.products.show', ['product' => $product->public_id]))->assertOk();
        $this->actingAs($owner)->get('/owner/products/'.$product->id)->assertNotFound();
        $this->actingAs($owner)->get(route('owner.products.show', ['product' => $outsideProduct->public_id]))->assertNotFound();
    }

    public function test_owner_can_create_product_with_modifier_and_integer_idr_price(): void
    {
        [$owner, $store] = $this->context();
        $category = Category::query()->create(['store_id' => $store->id, 'name' => 'Makanan', 'is_active' => true]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Porsi', 'symbol' => 'porsi', 'is_active' => true]);

        Livewire::actingAs($owner)->test(ProductForm::class)
            ->set('storePublicId', $store->public_id)->set('categoryPublicId', $category->public_id)->set('unitPublicId', $unit->public_id)
            ->set('name', 'Nasi Goreng')->set('sku', 'NAS-002')->set('sellingPrice', '25.000')->set('costPrice', '12.000')->set('minimumStock', '2')
            ->set('groups', [['name' => 'Level gula', 'is_required' => true, 'values' => [['name' => 'Normal', 'price_adjustment' => '0', 'is_default' => true]]]])
            ->call('save')->assertRedirect();

        $product = Product::query()->where('sku', 'NAS-002')->firstOrFail();
        $this->assertSame(25000, $product->selling_price);
        $this->assertSame(1, $product->optionGroups()->count());
        $this->assertSame(1, $product->optionGroups()->first()->values()->count());
    }

    public function test_owner_can_adjust_stock_through_canonical_action_and_category_cannot_disable_active_products(): void
    {
        [$owner, $store] = $this->context(); $product = $this->product($store, 'Kopi', 'KOP-001'); Inventory::query()->create(['product_id' => $product->id, 'quantity' => 3]);
        Livewire::actingAs($owner)->test(ProductShow::class, ['product' => $product->public_id])->call('openAdjustment')->set('direction', 'in')->set('quantity', '4')->set('reason', 'Restock')->call('saveAdjustment');
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'quantity' => 7]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => StockMovementType::AdjustmentIn->value, 'performed_by' => $owner->id]);

        $category = Category::query()->create(['store_id' => $store->id, 'name' => 'Kopi', 'is_active' => true]); $product->update(['category_id' => $category->id]);
        Livewire::actingAs($owner)->test(CategoryIndex::class)->call('toggle', $category->public_id)->assertHasErrors();
        $this->assertTrue($category->refresh()->is_active);
    }

    /** @return array{User, Store, Store} */
    private function context(): array
    {
        $store = Store::query()->create(['name' => 'Owner Store', 'store_code' => 'OWN']); $outside = Store::query()->create(['name' => 'Outside Store', 'store_code' => 'OUT']);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $store->id]); $owner->workspaceStores()->sync([$store->id => ['is_default' => true]]);
        return [$owner, $store, $outside];
    }

    private function product(Store $store, string $name, string $sku): Product
    {
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Unit '.$sku, 'symbol' => 'u-'.$sku, 'is_active' => true]);
        return Product::query()->create(['store_id' => $store->id, 'unit_id' => $unit->id, 'name' => $name, 'sku' => $sku, 'product_type' => ProductType::Stockable, 'selling_price' => 7000, 'cost_price' => 3000, 'minimum_stock' => 1, 'is_active' => true]);
    }
}
