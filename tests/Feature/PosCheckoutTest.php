<?php

namespace Tests\Feature;

use App\Actions\Inventory\RecordInitialStockAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Livewire\Cashier\PosCheckout as CashierPosCheckout;
use App\Livewire\Pos\Checkout;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_cashier_pos_route_renders_the_livewire_workspace(): void
    {
        [$cashier] = $this->prepareCheckoutContext();

        $this->actingAs($cashier)
            ->get('/cashier/pos')
            ->assertOk()
            ->assertSee('NasLabs POS')
            ->assertSee('Pilih produk');

        Livewire::actingAs($cashier)
            ->test(CashierPosCheckout::class)
            ->assertStatus(200);
    }

    public function test_canonical_cashier_pos_route_redirects_without_active_shift(): void
    {
        $store = Store::query()->create(['name' => 'Toko Tanpa Shift', 'slug' => 'toko-tanpa-shift']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier]);

        $this->actingAs($cashier)
            ->get('/cashier/pos')
            ->assertRedirect('/cashier/open-shift');
    }

    public function test_legacy_pos_checkout_route_uses_the_cashier_layout(): void
    {
        [$cashier] = $this->prepareCheckoutContext();

        $this->actingAs($cashier)
            ->get('/cashier/pos-checkout')
            ->assertOk()
            ->assertSee('NasLabs POS');
    }

    public function test_pos_workspace_renders_product_search_and_category_filter(): void
    {
        [$cashier, $product, $category] = $this->prepareCheckoutContext();

        $component = $this->checkout($cashier);

        $component
            ->assertStatus(200)
            ->assertSee($product->name)
            ->assertSee($category->name)
            ->set('search', $product->sku)
            ->assertSee($product->name)
            ->set('selectedCategoryId', $category->id)
            ->assertSee($product->name);
    }

    public function test_product_can_be_added_and_cart_quantity_can_change(): void
    {
        [$cashier, $product] = $this->prepareCheckoutContext();

        $component = $this->checkout($cashier)
            ->call('addProduct', $product->id)
            ->assertSet('cart.0.quantity', 1)
            ->call('increaseQuantity', 0)
            ->assertSet('cart.0.quantity', 2)
            ->call('decreaseQuantity', 0)
            ->assertSet('cart.0.quantity', 1)
            ->call('removeItem', 0)
            ->assertSet('cart', []);

        $this->assertSame(0, $component->get('grandTotal'));
    }

    public function test_checkout_preview_uses_sale_calculation_and_empty_cart_cannot_checkout(): void
    {
        [$cashier, $product] = $this->prepareCheckoutContext();

        $component = $this->checkout($cashier)
            ->call('addProduct', $product->id)
            ->assertSet('subtotal', 10000)
            ->assertSet('grandTotal', 10000);

        $component->call('clearCart')->assertSet('cart', []);
        $component->call('createPendingSale')->assertHasErrors('items');
    }

    public function test_barcode_scan_adds_matching_product_and_inactive_shift_redirects(): void
    {
        [$cashier, $product] = $this->prepareCheckoutContext();

        $this->checkout($cashier)
            ->set('search', $product->barcode)
            ->call('scanProduct')
            ->assertSet('cart.0.product_id', $product->id);

        $cashier->cashierShifts()->update(['status' => 'closed']);

        $this->actingAs($cashier);
        Filament::setCurrentPanel('cashier');

        Livewire::test(Checkout::class)->assertRedirect('/cashier/open-shift');
    }

    /** @return \Livewire\Testing\Testable<Checkout> */
    private function checkout(User $cashier): mixed
    {
        $this->actingAs($cashier);
        Filament::setCurrentPanel('cashier');

        return Livewire::test(Checkout::class);
    }

    /** @return array{User, Product, Category} */
    private function prepareCheckoutContext(): array
    {
        $store = Store::query()->create(['name' => 'Toko POS uji', 'slug' => 'toko-pos-uji']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Pcs', 'symbol' => 'pcs', 'is_active' => true]);
        $category = Category::query()->create(['store_id' => $store->id, 'name' => 'Minuman', 'is_active' => true]);
        $product = Product::query()->create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'name' => 'Kopi uji',
            'sku' => 'SKU-KOPI-UJI',
            'barcode' => '899000000001',
            'product_type' => ProductType::Stockable,
            'cost_price' => 4000,
            'selling_price' => 10000,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);

        app(RecordInitialStockAction::class)->handle($product, $cashier, 10);
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);
        $shift->update(['store_id' => $store->id]);

        return [$cashier, $product, $category];
    }
}
