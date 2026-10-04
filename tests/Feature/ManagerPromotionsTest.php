<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Livewire\Manager\Promotions\Index;
use App\Livewire\Manager\Promotions\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerPromotionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_store_scoped_promotions_and_numeric_routes_are_not_accepted(): void
    {
        [$manager, $cashier, $store] = $this->context('m6-promotions');
        $promotion = $this->promotion($store, 'Weekend Hemat', ['type' => 'percentage', 'value' => 10]);
        $other = $this->context('m6-other');
        $otherPromotion = $this->promotion($other[2], 'Promo Store Lain');

        $this->actingAs($manager)
            ->get(route('manager.promotions.index'))
            ->assertOk()
            ->assertSeeText('Promosi & diskon')
            ->assertSee($promotion->name)
            ->assertDontSee($otherPromotion->name);
        $this->actingAs($manager)->get(route('manager.promotions.show', ['promotion' => $promotion->public_id]))->assertOk()->assertSee('Detail Promo');
        $this->actingAs($manager)->get('/manager/promotions/'.$promotion->id)->assertNotFound();
        $this->actingAs($manager)->get(route('manager.promotions.show', ['promotion' => $otherPromotion->public_id]))->assertNotFound();
        $this->actingAs($cashier)->get(route('manager.promotions.index'))->assertForbidden();
    }

    public function test_manager_can_filter_create_edit_and_toggle_product_promotion(): void
    {
        [$manager, , $store] = $this->context('m6-crud');
        $product = $this->product($store, 'Kopi Susu');
        $scheduled = $this->promotion($store, 'Promo Mendatang', ['starts_at' => now()->addDay(), 'is_active' => true]);
        $expired = $this->promotion($store, 'Promo Lama', ['ends_at' => now()->subDay(), 'is_active' => true]);

        Livewire::actingAs($manager)->test(Index::class)
            ->set('search', 'Mendatang')->assertSee($scheduled->name)->assertDontSee($expired->name)
            ->set('search', '')->set('status', 'expired')->assertSee($expired->name)->assertDontSee($scheduled->name)
            ->call('openCreate')
            ->set('promotionName', 'Kopi Pagi')
            ->set('promotionType', 'fixed')
            ->set('promotionValue', '5000')
            ->set('productPublicId', $product->public_id)
            ->call('saveCreate');

        $created = Promotion::query()->where('name', 'Kopi Pagi')->firstOrFail();
        $this->assertSame($store->id, $created->store_id);
        $this->assertSame($product->id, $created->product_id);
        $this->assertNotNull($created->public_id);

        Livewire::actingAs($manager)->test(Show::class, ['promotion' => $created->public_id])
            ->call('openEdit')
            ->set('promotionName', 'Kopi Pagi Baru')
            ->set('promotionValue', '7000')
            ->call('saveEdit');
        $this->assertDatabaseHas('promotions', ['id' => $created->id, 'name' => 'Kopi Pagi Baru', 'value' => 7000]);

        Livewire::actingAs($manager)->test(Show::class, ['promotion' => $created->public_id])->call('toggleActive');
        $this->assertDatabaseHas('promotions', ['id' => $created->id, 'is_active' => false]);
    }

    public function test_promotion_rules_reject_invalid_percentage_and_explain_usage_limit(): void
    {
        [$manager, , $store] = $this->context('m6-validation');
        $promotion = $this->promotion($store, 'Promo Terbatas');

        Livewire::actingAs($manager)->test(Index::class)
            ->call('openCreate')
            ->set('promotionName', 'Tidak Valid')
            ->set('promotionType', 'percentage')
            ->set('promotionValue', '101')
            ->call('saveCreate')
            ->assertHasErrors('promotionValue');

        $this->assertDatabaseMissing('promotions', ['name' => 'Tidak Valid']);
        $this->actingAs($manager)->get(route('manager.promotions.show', ['promotion' => $promotion->public_id]))->assertSee('belum memiliki usage ledger');
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

    private function product(Store $store, string $name): Product
    {
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Unit '.$name, 'symbol' => 'u', 'is_active' => true]);

        return Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => $name,
            'sku' => 'SKU-'.strtoupper(substr(md5($name.$store->id), 0, 6)),
            'product_type' => ProductType::Stockable,
            'selling_price' => 15000,
            'cost_price' => 8000,
            'minimum_stock' => 1,
            'is_active' => true,
        ]);
    }

    private function promotion(Store $store, string $name, array $overrides = []): Promotion
    {
        return Promotion::query()->create(array_merge([
            'store_id' => $store->id,
            'name' => $name,
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ], $overrides));
    }
}
