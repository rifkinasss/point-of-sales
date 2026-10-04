<?php

namespace Tests\Feature;

use App\Actions\Inventory\RecordInitialStockAction;
use App\Actions\Sale\CreatePendingSaleAction;
use App\Actions\Sale\ProcessPaymentAction;
use App\Actions\Sale\RefundSaleAction;
use App\Actions\Sale\VoidSaleAction;
use App\Actions\Shift\CloseShiftAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Enums\CashMovementType;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_sale_can_be_voided_without_payment_or_inventory_mutation(): void
    {
        [$manager, $product, $shift] = $this->prepareSaleContext(10);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $voided = app(VoidSaleAction::class)->handle($sale, $manager, 'Pelanggan membatalkan pesanan');

        $this->assertSame(SaleStatus::Voided, $voided->status);
        $this->assertSame(10, $product->inventory()->value('quantity'));
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseHas('audit_logs', ['event' => 'sale_voided', 'auditable_id' => $sale->id]);
    }

    public function test_paid_sale_cannot_be_voided_and_same_sale_cannot_be_paid_twice(): void
    {
        [$manager, $product, $shift] = $this->prepareSaleContext(10);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        app(ProcessPaymentAction::class)->handle($sale, $manager, PaymentMethod::Cash, 20000);

        try {
            app(VoidSaleAction::class)->handle($sale, $manager, 'Tidak boleh void setelah bayar');
            $this->fail('Paid sale should not be voidable.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(ValidationException::class);
        app(ProcessPaymentAction::class)->handle($sale, $manager, PaymentMethod::Cash, 20000);
    }

    public function test_unauthorized_cashier_cannot_void_or_refund(): void
    {
        [$manager, $product, $shift] = $this->prepareSaleContext(10);
        $cashier = User::factory()->create(['store_id' => $manager->store_id, 'role' => UserRole::Cashier]);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $this->expectException(ValidationException::class);
        app(VoidSaleAction::class)->handle($sale, $cashier, 'Tidak berwenang');
    }

    public function test_invoice_numbers_are_unique_for_multiple_sales(): void
    {
        [$manager, $product, $shift] = $this->prepareSaleContext(10);
        $first = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);
        $second = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);

        $this->assertNotSame($first->invoice_number, $second->invoice_number);
        $this->assertStringStartsWith('POS-'.now()->format('Ymd').'-', $first->invoice_number);
        $this->assertDatabaseCount('sales', 2);
    }

    public function test_cross_store_customer_and_sale_payment_are_rejected(): void
    {
        [$manager, $product, $shift] = $this->prepareSaleContext(10);
        $otherStore = Store::query()->create(['name' => 'Toko lain', 'slug' => 'toko-lain']);
        $otherCustomer = Customer::query()->create(['store_id' => $otherStore->id, 'code' => 'CUST-LAIN', 'name' => 'Pelanggan lain', 'is_active' => true]);

        $this->expectException(ValidationException::class);
        app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]], 0, null, null, null, $otherCustomer->id);
    }

    public function test_cash_payment_marks_sale_paid_and_reduces_stock(): void
    {
        [$cashier, $product, $shift] = $this->prepareSaleContext(10);

        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        $paidSale = app(ProcessPaymentAction::class)->handle(
            $sale,
            $cashier,
            PaymentMethod::Cash,
            25000,
        );

        $this->assertSame(SaleStatus::Paid, $paidSale->status);
        $this->assertSame(20000, $paidSale->grand_total);
        $this->assertSame(5000, $paidSale->payment->change_amount);
        $this->assertSame(8, $product->inventory()->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::SaleOut->value,
            'quantity_change' => -2,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Sale->value,
            'amount' => 20000,
            'reference_type' => 'payment',
        ]);

        $closedShift = app(CloseShiftAction::class)->handle($shift, $cashier, 120000);

        $this->assertSame(120000, $closedShift->expected_cash);
        $this->assertSame(0, $closedShift->cash_difference);
    }

    public function test_payment_is_rejected_when_stock_is_no_longer_sufficient(): void
    {
        [$cashier, $product, $shift] = $this->prepareSaleContext(1);

        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);

        try {
            app(ProcessPaymentAction::class)->handle($sale, $cashier, PaymentMethod::Cash, 20000);
            $this->fail('Insufficient stock should reject payment.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertDatabaseMissing('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Sale->value,
        ]);
    }

    public function test_non_cash_payment_does_not_create_cash_movement(): void
    {
        [$cashier, $product, $shift] = $this->prepareSaleContext(10);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        app(ProcessPaymentAction::class)->handle($sale, $cashier, PaymentMethod::Qris, 10000);

        $this->assertDatabaseCount('cash_movements', 1);
        $this->assertDatabaseMissing('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Sale->value,
        ]);
    }

    public function test_promotion_discount_is_calculated_server_side_and_reduces_grand_total(): void
    {
        [$cashier, $product, $shift] = $this->prepareSaleContext(10);
        $campaignId = DB::table('redeem_campaigns')->insertGetId([
            'name' => 'Diskon uji',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $codeId = DB::table('redeem_codes')->insertGetId([
            'campaign_id' => $campaignId,
            'code' => 'PROMO-UJI',
            'reusable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('redeem_code_rewards')->insert([
            'campaign_id' => $campaignId,
            'type' => 'discount_amount',
            'amount' => 5000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sale = app(CreatePendingSaleAction::class)->handle(
            $shift,
            $cashier,
            [['product_id' => $product->id, 'quantity' => 2]],
            1000,
            null,
            $codeId,
            'PROMO-UJI',
        );

        $this->assertSame(20000, $sale->subtotal);
        $this->assertSame(1000, $sale->transaction_discount_total);
        $this->assertSame(5000, $sale->promotion_discount_total);
        $this->assertSame(14000, $sale->grand_total);
    }

    public function test_recipe_payment_reduces_ingredients_and_not_finished_product(): void
    {
        [$cashier, $ingredient, $finished, $shift] = $this->prepareRecipeSaleContext();

        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $finished->id, 'quantity' => 3],
        ]);
        $sale->update(['store_id' => $cashier->store_id]);

        app(ProcessPaymentAction::class)->handle($sale, $cashier, PaymentMethod::Cash, 30000);

        $this->assertSame(4, $ingredient->inventory()->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $ingredient->id,
            'reference_type' => 'recipe_sale',
            'reference_id' => $sale->id,
            'quantity_change' => -6,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $finished->id,
            'reference_id' => $sale->id,
        ]);
    }

    public function test_recipe_refund_inverts_original_ingredient_movements_and_is_idempotent(): void
    {
        [$cashier, $ingredient, $finished, $shift] = $this->prepareRecipeSaleContext();
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $finished->id, 'quantity' => 3],
        ]);
        $sale->update(['store_id' => $cashier->store_id]);
        app(ProcessPaymentAction::class)->handle($sale, $cashier, PaymentMethod::Cash, 30000);

        $return = app(RefundSaleAction::class)->handle($sale, $cashier, 'Pesanan dibatalkan');

        $this->assertSame(10, $ingredient->inventory()->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $ingredient->id,
            'type' => StockMovementType::SaleReturnIn->value,
            'reference_type' => 'sale_return',
            'reference_id' => $return->id,
            'quantity_change' => 6,
            'quantity_before' => 4,
            'quantity_after' => 10,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $finished->id,
            'reference_id' => $return->id,
        ]);

        $this->expectException(ValidationException::class);
        app(RefundSaleAction::class)->handle($sale, $cashier, 'Refund kedua');
    }

    public function test_non_recipe_refund_restores_sold_product_with_inverse_movement(): void
    {
        [$cashier, $product, $shift] = $this->prepareSaleContext(10);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $cashier, [
            ['product_id' => $product->id, 'quantity' => 2],
        ]);
        app(ProcessPaymentAction::class)->handle($sale, $cashier, PaymentMethod::Cash, 20000);

        $return = app(RefundSaleAction::class)->handle($sale, $cashier, 'Produk dikembalikan');

        $this->assertSame(10, $product->inventory()->value('quantity'));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::SaleReturnIn->value,
            'reference_id' => $return->id,
            'quantity_change' => 2,
            'quantity_before' => 8,
            'quantity_after' => 10,
        ]);
        $this->assertDatabaseHas('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Refund->value,
            'amount' => 20000,
            'reference_type' => 'sale_return',
            'reference_id' => $return->id,
        ]);
    }

    /** @return array{User, Product, CashierShift} */
    private function prepareSaleContext(int $stock): array
    {
        $store = Store::query()->create(['name' => 'Toko penjualan uji', 'slug' => 'toko-penjualan-uji']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager]);
        $unit = Unit::query()->create([
            'store_id' => $store->id,
            'name' => 'Pcs',
            'symbol' => 'pcs',
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => 'Produk uji',
            'sku' => 'SKU-UJI-001',
            'product_type' => 'stockable',
            'cost_price' => 5000,
            'selling_price' => 10000,
            'minimum_stock' => 2,
            'is_active' => true,
        ]);

        app(RecordInitialStockAction::class)->handle($product, $cashier, $stock);
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);
        $shift->update(['store_id' => $store->id]);

        return [$cashier, $product, $shift];
    }

    /** @return array{User, Product, Product, CashierShift} */
    private function prepareRecipeSaleContext(): array
    {
        $store = Store::query()->create(['name' => 'Toko resep uji', 'slug' => 'toko-resep-uji']);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Pcs', 'symbol' => 'pcs', 'is_active' => true]);
        $ingredient = Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => 'Bahan uji',
            'sku' => 'SKU-BAHAN-001',
            'product_type' => 'stockable',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);
        $finished = Product::query()->create([
            'store_id' => $store->id,
            'unit_id' => $unit->id,
            'name' => 'Produk resep uji',
            'sku' => 'SKU-RESEP-001',
            'product_type' => 'stockable',
            'cost_price' => 3000,
            'selling_price' => 10000,
            'minimum_stock' => 0,
            'is_active' => true,
        ]);

        app(RecordInitialStockAction::class)->handle($ingredient, $cashier, 10);
        $finished->recipeIngredients()->create([
            'ingredient_product_id' => $ingredient->id,
            'quantity_required' => 2,
        ]);
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);
        $shift->update(['store_id' => $store->id]);

        return [$cashier, $ingredient, $finished, $shift];
    }
}
