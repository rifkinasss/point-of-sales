<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Livewire\Owner\Customers\Form as CustomerForm;
use App\Livewire\Owner\Promotions\Index as PromotionIndex;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerCustomerPromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_customer_and_view_store_scoped_history_by_invoice(): void
    {
        [$owner, $store, $outside] = $this->context(); $customer = Customer::query()->create(['store_id' => $store->id, 'code' => 'MBR-001', 'name' => 'Andi Lama', 'is_active' => true]); $cashier = User::factory()->create(['role' => UserRole::Cashier, 'store_id' => $store->id, 'is_active' => true]); $shift = CashierShift::query()->create(['store_id' => $store->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Open, 'opened_at' => now(), 'opening_cash' => 0]); $sale = Sale::query()->create(['store_id' => $store->id, 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id, 'customer_id' => $customer->id, 'invoice_number' => 'NAS-001', 'status' => SaleStatus::Paid, 'subtotal' => 25000, 'grand_total' => 25000, 'completed_at' => now()]);
        Livewire::actingAs($owner)->test(CustomerForm::class)->set('storePublicId', $store->public_id)->set('code', 'MBR-002')->set('name', 'Budi Baru')->set('phone', '0812')->set('email', 'budi@example.com')->call('save')->assertRedirect();
        $created = Customer::query()->where('code', 'MBR-002')->firstOrFail(); $this->assertNotSame((string) $created->id, $created->public_id); $this->actingAs($owner)->get(route('owner.customers.show', ['customer' => $customer->public_id]))->assertOk()->assertSee($sale->invoice_number)->assertSee($customer->name);
        $outsideCustomer = Customer::query()->create(['store_id' => $outside->id, 'code' => 'OUT-001', 'name' => 'Outside', 'is_active' => true]); $this->actingAs($owner)->get(route('owner.customers.show', ['customer' => $outsideCustomer->public_id]))->assertNotFound(); $this->actingAs($owner)->get('/owner/customers/'.$customer->id)->assertNotFound();
    }

    public function test_owner_can_create_and_edit_canonical_promotion_without_inventing_code(): void
    {
        [$owner, $store] = $this->context(); $product = $this->product($store, 'Kopi', 'KOP-001');
        Livewire::actingAs($owner)->test(PromotionIndex::class)->set('storePublicId', $store->public_id)->set('promotionName', 'Kopi Pagi')->set('promotionType', 'percentage')->set('promotionValue', '20')->set('productPublicId', $product->public_id)->call('saveCreate')->assertRedirect();
        $promotion = Promotion::query()->withoutGlobalScopes()->where('store_id', $store->id)->where('name', 'Kopi Pagi')->firstOrFail(); $this->assertNotNull($promotion->public_id); $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'value' => 20, 'product_id' => $product->id]);
        $this->actingAs($owner)->get(route('owner.promotions.show', ['promotion' => $promotion->public_id]))->assertOk()->assertSee('Kopi Pagi')->assertDontSee('Kode promo'); $this->actingAs($owner)->get('/owner/promotions/'.$promotion->id)->assertNotFound();
    }

    public function test_owner_cannot_select_product_from_unauthorized_store_for_promotion(): void
    {
        [$owner, $store, $outside] = $this->context(); $outsideProduct = $this->product($outside, 'Rahasia', 'OUT-001');
        Livewire::actingAs($owner)->test(PromotionIndex::class)->set('storePublicId', $store->public_id)->set('promotionName', 'Invalid')->set('promotionType', 'fixed')->set('promotionValue', '1000')->set('productPublicId', $outsideProduct->public_id)->call('saveCreate')->assertStatus(404);
    }

    /** @return array{User, Store, Store} */
    private function context(): array
    {
        $store = Store::query()->create(['name' => 'Owner Store', 'store_code' => 'OWN']); $outside = Store::query()->create(['name' => 'Outside Store', 'store_code' => 'OUT']); $owner = User::factory()->create(['role' => UserRole::Owner, 'store_id' => $store->id, 'is_active' => true]); $owner->workspaceStores()->sync([$store->id => ['is_default' => true]]); return [$owner, $store, $outside];
    }

    private function product(Store $store, string $name, string $sku): Product
    {
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Unit '.$sku, 'symbol' => 'u-'.$sku, 'is_active' => true]); return Product::query()->create(['store_id' => $store->id, 'unit_id' => $unit->id, 'name' => $name, 'sku' => $sku, 'product_type' => ProductType::Stockable, 'selling_price' => 10000, 'cost_price' => 5000, 'minimum_stock' => 0, 'is_active' => true]);
    }
}
