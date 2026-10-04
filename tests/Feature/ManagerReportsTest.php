<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Queries\ManagerReportQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_reports_and_cashier_is_forbidden(): void
    {
        [$manager, $cashier, $store] = $this->context('m7-access');

        $this->actingAs($manager)->get(route('manager.reports.index'))->assertOk()->assertSee('Ringkasan operasional');
        $this->actingAs($manager)->get(route('manager.reports.sales'))->assertOk()->assertSee('Laporan Penjualan');
        $this->actingAs($manager)->get(route('manager.reports.print', ['report' => 'sales']))->assertOk()->assertSee($store->name);
        $this->actingAs($cashier)->get(route('manager.reports.index'))->assertForbidden();
    }

    public function test_sales_and_payment_reports_use_store_scope_and_split_allocations(): void
    {
        [$manager, $cashier, $store] = $this->context('m7-sales');
        $shift = $this->shift($store, $cashier);
        $sale = Sale::query()->create(['store_id' => $store->id, 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id, 'invoice_number' => 'M7-001', 'status' => SaleStatus::Paid, 'subtotal' => 12000, 'item_discount_total' => 1000, 'promotion_discount_total' => 0, 'transaction_discount_total' => 0, 'grand_total' => 11000, 'completed_at' => now()]);
        SaleItem::query()->create(['sale_id' => $sale->id, 'product_id' => null, 'product_name' => 'Kopi', 'sku' => 'KOP-1', 'unit_name' => 'Gelas', 'quantity' => 2, 'unit_price' => 6000, 'discount_amount' => 1000, 'line_total' => 11000]);
        Payment::query()->create(['sale_id' => $sale->id, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Completed, 'amount_due' => 6000, 'amount_received' => 6000, 'change_amount' => 0, 'paid_at' => now(), 'received_by' => $cashier->id]);
        Payment::query()->create(['sale_id' => $sale->id, 'method' => PaymentMethod::Qris, 'status' => PaymentStatus::Completed, 'amount_due' => 5000, 'amount_received' => 5000, 'change_amount' => 0, 'paid_at' => now(), 'received_by' => $cashier->id]);
        $other = $this->context('m7-other');
        $otherShift = $this->shift($other[2], $other[1]);
        Sale::query()->create(['store_id' => $other[2]->id, 'cashier_shift_id' => $otherShift->id, 'cashier_id' => $other[1]->id, 'invoice_number' => 'OTHER-001', 'status' => SaleStatus::Paid, 'subtotal' => 99999, 'grand_total' => 99999, 'completed_at' => now()]);

        $sales = app(ManagerReportQuery::class)->report($manager, 'sales', ['datePreset' => 'today']);
        $payments = app(ManagerReportQuery::class)->report($manager, 'payments', ['datePreset' => 'today']);
        $this->assertSame(1, $sales['summary']['transactions']);
        $this->assertSame(11000, $sales['summary']['net']);
        $this->assertSame(11000, $payments['summary']['completed']);
        $this->assertCount(2, $payments['rows']);
        $this->assertNotContains('OTHER-001', array_column($sales['rows'], 'invoice'));

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Reports\Index::class, ['report' => 'sales'])->assertSee('M7-001')->assertDontSee('OTHER-001');
    }

    public function test_cash_report_excludes_active_shift_from_actual_cash(): void
    {
        [$manager, $cashier, $store] = $this->context('m7-cash');
        $open = $this->shift($store, $cashier);
        CashMovement::query()->create(['store_id' => $store->id, 'shift_id' => $open->id, 'user_id' => $cashier->id, 'type' => 'opening', 'amount' => 10000]);
        CashMovement::query()->create(['store_id' => $store->id, 'shift_id' => $open->id, 'user_id' => $cashier->id, 'type' => 'sale', 'amount' => 5000]);
        $closed = $this->shift($store, $cashier, ['status' => ShiftStatus::Closed, 'closed_at' => now(), 'closing_cash_actual' => 9000, 'expected_cash' => 9000, 'cash_difference' => 0]);
        CashMovement::query()->create(['store_id' => $store->id, 'shift_id' => $closed->id, 'user_id' => $cashier->id, 'type' => 'opening', 'amount' => 9000]);

        $cash = app(ManagerReportQuery::class)->report($manager, 'cash', ['datePreset' => 'today']);
        $this->assertSame(9000, $cash['summary']['actual']);
        $this->assertSame(24000, $cash['summary']['expected']);
        $this->assertCount(1, $cash['rows']);
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

    private function shift(Store $store, User $cashier, array $overrides = []): CashierShift
    {
        return CashierShift::query()->create(array_merge(['store_id' => $store->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Open, 'opened_at' => now(), 'opening_cash' => 0], $overrides));
    }
}
