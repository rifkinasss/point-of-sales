<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Queries\ManagerCustomerQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerCustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_search_customers_and_open_store_scoped_detail(): void
    {
        [$manager, $cashier, $store] = $this->context('m5-customers');
        $customer = Customer::query()->create(['store_id' => $store->id, 'code' => 'MBR-001', 'name' => 'Andi Pratama', 'phone' => '08123456789', 'email' => 'andi@example.com', 'is_active' => true]);
        $sale = $this->sale($store, $cashier, $customer, 'M5-001', 125000, SaleStatus::Paid);
        $other = $this->context('m5-other');
        $otherCustomer = Customer::query()->create(['store_id' => $other[2]->id, 'code' => 'MBR-999', 'name' => 'Pelanggan Rahasia', 'is_active' => true]);

        $this->actingAs($manager)->get(route('manager.customers.index'))->assertOk()->assertSee('Database pelanggan')->assertSee($customer->name)->assertDontSee($otherCustomer->name);
        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Customers\Index::class)
            ->set('search', '08123456789')->assertSee($customer->name)
            ->set('search', 'andi@example.com')->assertSee($customer->name)
            ->set('search', 'MBR-001')->assertSee($customer->name);
        $this->actingAs($manager)->get(route('manager.customers.show', ['customer' => $customer->public_id]))->assertOk()->assertSee('Riwayat transaksi')->assertSee($sale->invoice_number);
        $this->actingAs($manager)->get('/manager/customers/'.$customer->id)->assertNotFound();
        $this->actingAs($manager)->get(route('manager.customers.show', ['customer' => $otherCustomer->public_id]))->assertNotFound();
        $this->actingAs($cashier)->get(route('manager.customers.index'))->assertForbidden();
    }

    public function test_customer_metrics_exclude_pending_and_voided_sales_and_allow_profile_edit(): void
    {
        [$manager, $cashier, $store] = $this->context('m5-metrics');
        $customer = Customer::query()->create(['store_id' => $store->id, 'code' => 'MBR-002', 'name' => 'Budi', 'phone' => '0812', 'is_active' => true]);
        $this->sale($store, $cashier, $customer, 'M5-PAID-1', 100000, SaleStatus::Paid);
        $this->sale($store, $cashier, $customer, 'M5-PAID-2', 50000, SaleStatus::Paid);
        $this->sale($store, $cashier, $customer, 'M5-VOID', 999999, SaleStatus::Voided);
        $metrics = app(ManagerCustomerQuery::class)->customerMetrics($manager, $customer);
        $this->assertSame(2, $metrics['transactions']);
        $this->assertSame(150000, $metrics['spend']);
        $this->assertSame(75000, $metrics['average']);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Customers\Show::class, ['customer' => $customer->public_id])
            ->call('openEdit')->set('name', 'Budi Baru')->set('phone', '0899')->set('email', 'budi@example.com')->call('saveEdit');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Budi Baru', 'phone' => '0899', 'email' => 'budi@example.com']);
    }

    public function test_walk_in_customer_is_not_in_member_list_and_transaction_history_links_to_m2(): void
    {
        [$manager, $cashier, $store] = $this->context('m5-history');
        $customer = Customer::query()->create(['store_id' => $store->id, 'code' => 'MBR-003', 'name' => 'Sari', 'is_active' => true]);
        $sale = $this->sale($store, $cashier, $customer, 'M5-LINK', 20000, SaleStatus::Paid);
        $response = $this->actingAs($manager)->get(route('manager.customers.show', ['customer' => $customer->public_id]));
        $response->assertSee(route('manager.transactions.show', ['sale' => $sale->public_id]), false)->assertDontSee('Walk-in Customer');
    }

    /** @return array{User, User, Store} */
    private function context(string $slug): array
    {
        $store = Store::query()->create(['name' => 'Store '.$slug, 'slug' => $slug]);
        return [User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager, 'is_active' => true]), User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier, 'is_active' => true]), $store];
    }

    private function sale(Store $store, User $cashier, Customer $customer, string $invoice, int $total, SaleStatus $status): Sale
    {
        $shift = CashierShift::query()->firstOrCreate(['store_id' => $store->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Open], ['opened_at' => now(), 'opening_cash' => 0]);
        return Sale::query()->create(['store_id' => $store->id, 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id, 'customer_id' => $customer->id, 'invoice_number' => $invoice, 'status' => $status, 'subtotal' => $total, 'grand_total' => $total, 'completed_at' => $status === SaleStatus::Paid ? now() : null]);
    }
}
