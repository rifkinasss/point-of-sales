<?php

namespace Tests\Feature;

use App\Actions\Sale\AddPaymentAction;
use App\Actions\Sale\CreatePendingSaleAction;
use App\Actions\Sale\ProcessPaymentAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Queries\ManagerTransactionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerTransactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_store_scoped_transaction_index_and_search(): void
    {
        [$manager, $product, $shift] = $this->context('manager-transactions', 'Manager Transactions');
        $customer = Customer::query()->create(['store_id' => $manager->store_id, 'code' => 'CUST-M2', 'name' => 'Pelanggan M2', 'is_active' => true]);
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]], 0, null, null, null, $customer->id);
        app(ProcessPaymentAction::class)->handle($sale, $manager, PaymentMethod::Cash, 10000);

        $other = $this->context('other-transactions', 'Other Transactions');
        $otherSale = app(CreatePendingSaleAction::class)->handle($other[2], $other[0], [['product_id' => $other[1]->id, 'quantity' => 1]]);

        $this->actingAs($manager)->get(route('manager.transactions.index'))->assertOk()->assertSee('Riwayat transaksi');
        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Transactions\Index::class)
            ->set('search', 'Pelanggan M2')
            ->assertSee($sale->invoice_number)
            ->assertDontSee($otherSale->invoice_number)
            ->set('status', SaleStatus::Paid->value)
            ->set('paymentMethod', PaymentMethod::Cash->value)
            ->assertSee('Tunai');
    }

    public function test_cashier_is_forbidden_and_public_detail_is_store_scoped(): void
    {
        [$manager, $product, $shift] = $this->context('manager-detail', 'Manager Detail');
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);
        $other = $this->context('manager-detail-other', 'Manager Detail Other');
        $otherSale = app(CreatePendingSaleAction::class)->handle($other[2], $other[0], [['product_id' => $other[1]->id, 'quantity' => 1]]);

        $cashier = User::factory()->create(['store_id' => $manager->store_id, 'role' => UserRole::Cashier, 'is_active' => true]);
        $this->actingAs($cashier)->get(route('manager.transactions.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('manager.transactions.show', ['sale' => $sale->public_id]))->assertOk()->assertSee($sale->invoice_number);
        $this->actingAs($manager)->get('/manager/transactions/'.$sale->id)->assertNotFound();

        $this->actingAs($manager)->get(route('manager.transactions.show', ['sale' => $otherSale->public_id]))->assertNotFound();
    }

    public function test_split_payment_and_manager_receipt_are_available_without_numeric_urls(): void
    {
        [$manager, $product, $shift] = $this->context('manager-receipt', 'Manager Receipt');
        $sale = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);
        app(AddPaymentAction::class)->handle($sale, $manager, PaymentMethod::Cash, 4000, 4000);
        $paid = app(AddPaymentAction::class)->handle($sale, $manager, PaymentMethod::Qris, 6000, 6000, 'QR-M2-001');

        $this->actingAs($manager)->get(route('manager.transactions.show', ['sale' => $paid->public_id]))->assertOk()->assertSee('Tunai')->assertSee('QRIS')->assertSee('QR-M2-001');
        $this->actingAs($manager)->get(route('cashier.receipt.print', ['sale' => $paid->public_id]))->assertOk()->assertSee('STRUK PEMBAYARAN');
    }

    public function test_manager_lifecycle_actions_use_confirmation_reason_and_current_state(): void
    {
        [$manager, $product, $shift] = $this->context('manager-actions', 'Manager Actions');
        $pending = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Transactions\Show::class, ['sale' => $pending->public_id])
            ->call('openAction', 'void')
            ->set('reason', 'Pelanggan membatalkan transaksi')
            ->call('confirmAction');
        $this->assertSame(SaleStatus::Voided, $pending->refresh()->status);

        $paid = app(CreatePendingSaleAction::class)->handle($shift, $manager, [['product_id' => $product->id, 'quantity' => 1]]);
        app(ProcessPaymentAction::class)->handle($paid, $manager, PaymentMethod::Cash, 10000);
        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Transactions\Show::class, ['sale' => $paid->public_id])
            ->call('openAction', 'refund')
            ->set('reason', 'Refund M2')
            ->call('confirmAction');
        $this->assertSame(SaleStatus::Refunded, $paid->refresh()->status);
    }

    public function test_transaction_query_paginates_newest_store_transactions(): void
    {
        [$manager, $product, $shift] = $this->context('manager-pagination', 'Manager Pagination');
        for ($index = 1; $index <= 21; $index++) {
            Sale::query()->create([
                'store_id' => $manager->store_id,
                'invoice_number' => 'M2-PAGE-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'cashier_shift_id' => $shift->id,
                'cashier_id' => $manager->id,
                'status' => SaleStatus::PendingPayment,
                'subtotal' => 10000,
                'grand_total' => 10000,
            ]);
        }

        $transactions = app(ManagerTransactionQuery::class)->paginate($manager, ['datePreset' => 'today', 'perPage' => 20]);

        $this->assertSame(21, $transactions->total());
        $this->assertSame(2, $transactions->lastPage());
    }

    /** @return array{User, Product, \App\Models\CashierShift} */
    private function context(string $slug, string $name): array
    {
        $store = Store::query()->create(['name' => $name, 'slug' => $slug]);
        $manager = User::factory()->create(['name' => 'Manager '.$name, 'store_id' => $store->id, 'role' => UserRole::Manager, 'is_active' => true]);
        $unit = Unit::query()->create(['store_id' => $store->id, 'name' => 'Pcs '.$slug, 'symbol' => 'pcs-'.$slug, 'is_active' => true]);
        $product = Product::query()->create(['store_id' => $store->id, 'unit_id' => $unit->id, 'name' => 'Produk '.$name, 'sku' => 'SKU-'.$slug, 'product_type' => 'non_stockable', 'cost_price' => 5000, 'selling_price' => 10000, 'minimum_stock' => 0, 'is_active' => true]);
        $shift = app(OpenShiftAction::class)->handle($manager, 100000);

        return [$manager, $product, $shift];
    }
}
