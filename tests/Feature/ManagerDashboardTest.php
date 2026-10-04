<?php

namespace Tests\Feature;

use App\Actions\Inventory\RecordInitialStockAction;
use App\Actions\Sale\CreatePendingSaleAction;
use App\Actions\Sale\ProcessPaymentAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Queries\ManagerDashboardQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_completed_sales_and_active_shifts_from_the_managers_store(): void
    {
        [$manager, $product, $shift] = $this->salesContext('manager-store', 'Manager Store');
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);
        $paid = app(ProcessPaymentAction::class)->handle($sale, $manager, PaymentMethod::Cash, 15000);

        [$otherManager, $otherProduct, $otherShift] = $this->salesContext('other-store', 'Other Store');
        $otherSale = app(CreatePendingSaleAction::class)->handle($otherShift, $otherManager, [['product_id' => $otherProduct->id, 'quantity' => 1]]);
        app(ProcessPaymentAction::class)->handle($otherSale, $otherManager, PaymentMethod::Qris, 10000);

        $dashboard = app(ManagerDashboardQuery::class)->dashboard($manager);

        $this->assertSame(1, $dashboard['transactionCount']);
        $this->assertSame($paid->grand_total, $dashboard['netSales']);
        $this->assertSame($paid->grand_total, $dashboard['paymentBreakdown']['cash']);
        $this->assertArrayNotHasKey('qris', $dashboard['paymentBreakdown']);
        $this->assertCount(1, $dashboard['activeShifts']);
        $this->assertSame($sale->invoice_number, $dashboard['recentSales']->first()->invoice_number);
        $this->assertNotSame($otherSale->invoice_number, $dashboard['recentSales']->first()->invoice_number);
        $this->assertSame(1, $dashboard['lowStock']);
    }

    public function test_manager_workspace_is_not_available_without_a_store_context(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager, 'is_active' => true, 'store_id' => null]);

        $this->actingAs($manager)->get('/manager')->assertForbidden();
    }

    /** @return array{User, Product, \App\Models\CashierShift} */
    private function salesContext(string $slug, string $name): array
    {
        $store = Store::query()->create(['name' => $name, 'slug' => $slug]);
        $manager = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Pcs '.$slug, 'symbol' => 'pcs-'.$slug, 'is_active' => true]);
        $product = Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => 'Produk '.$name,
            'sku' => 'SKU-'.$slug,
            'product_type' => 'stockable',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        app(RecordInitialStockAction::class)->handle($product, $manager, 2);
        $shift = app(OpenShiftAction::class)->handle($manager, 100000);

        return [$manager, $product, $shift];
    }
}
