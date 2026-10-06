<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerReportQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_reports_and_other_roles_cannot(): void
    {
        [$owner, $store] = $this->ownerContext('reports-access');
        $manager = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager, 'is_active' => true]);

        $this->actingAs($owner)->get(route('owner.reports.index'))->assertOk()->assertSee('Laporan & Analitik', false);
        $this->actingAs($manager)->get(route('owner.reports.index'))->assertForbidden();
    }

    public function test_report_aggregates_canonical_sales_and_split_payments_without_cross_store_data(): void
    {
        [$owner, $store] = $this->ownerContext('reports-sales');
        $other = Store::query()->create(['name' => 'Other Store', 'slug' => 'reports-other']);
        $customer = Customer::query()->withoutGlobalScopes()->create(['store_id' => $store->id, 'code' => 'RPT-CUST', 'name' => 'Customer Report']);
        $sale = Sale::query()->withoutGlobalScopes()->create([
            'store_id' => $store->id, 'invoice_number' => 'RPT-001', 'cashier_shift_id' => $this->shiftId($store), 'cashier_id' => $owner->id,
            'customer_id' => $customer->id, 'status' => SaleStatus::Paid, 'subtotal' => 20000, 'item_discount_total' => 1000,
            'promotion_discount_total' => 500, 'transaction_discount_total' => 500, 'grand_total' => 18000, 'completed_at' => now(),
        ]);
        SaleItem::query()->create(['sale_id' => $sale->id, 'product_name' => 'Kopi Snapshot', 'sku' => 'KOP-RPT', 'unit_name' => 'Gelas', 'quantity' => 2, 'unit_price' => 10000, 'discount_amount' => 1000, 'line_total' => 19000]);
        Payment::query()->create(['sale_id' => $sale->id, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Completed, 'amount_due' => 10000, 'amount_received' => 10000, 'change_amount' => 0, 'paid_at' => now(), 'received_by' => $owner->id]);
        Payment::query()->create(['sale_id' => $sale->id, 'method' => PaymentMethod::Qris, 'status' => PaymentStatus::Completed, 'amount_due' => 8000, 'amount_received' => 8000, 'change_amount' => 0, 'paid_at' => now(), 'received_by' => $owner->id]);
        Sale::query()->withoutGlobalScopes()->create(['store_id' => $other->id, 'invoice_number' => 'OTHER-RPT', 'cashier_shift_id' => $this->shiftId($other), 'cashier_id' => $owner->id, 'status' => SaleStatus::Paid, 'subtotal' => 90000, 'grand_total' => 90000, 'completed_at' => now()]);

        $report = app(OwnerReportQuery::class)->report($owner, ['store' => $store->public_id, 'datePreset' => 'today', 'productSort' => 'net']);

        $this->assertSame(18000, $report['summary']['net']);
        $this->assertSame(1, $report['summary']['transactions']);
        $this->assertSame(10000, $report['summary']['cash']);
        $this->assertSame(8000, $report['summary']['nonCash']);
        $this->assertCount(2, $report['payments']);
        $this->assertSame('Kopi Snapshot', $report['products'][0]['product']);
        $this->assertCount(1, $report['customers']);

        $all = app(OwnerReportQuery::class)->report($owner, ['store' => 'all', 'datePreset' => 'today', 'productSort' => 'net']);
        $this->assertSame(1, $all['summary']['transactions']);
        $this->assertNotContains('OTHER-RPT', array_column($report['products'], 'product'));
    }

    public function test_pending_and_voided_sales_are_not_reported(): void
    {
        [$owner, $store] = $this->ownerContext('reports-status');
        foreach ([SaleStatus::PendingPayment, SaleStatus::Voided] as $index => $status) {
            Sale::query()->withoutGlobalScopes()->create(['store_id' => $store->id, 'invoice_number' => 'RPT-STATUS-'.$index, 'cashier_shift_id' => $this->shiftId($store), 'cashier_id' => $owner->id, 'status' => $status, 'subtotal' => 50000, 'grand_total' => 50000, 'completed_at' => now()]);
        }

        $report = app(OwnerReportQuery::class)->report($owner, ['store' => 'all', 'datePreset' => 'today', 'productSort' => 'net']);

        $this->assertSame(0, $report['summary']['transactions']);
        $this->assertSame(0, $report['summary']['net']);
    }

    /** @return array{User, Store} */
    private function ownerContext(string $slug): array
    {
        $store = Store::query()->create(['name' => 'Store '.$slug, 'slug' => $slug]);
        return [User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Owner, 'is_active' => true]), $store];
    }

    private function shiftId(Store $store): int
    {
        return (int) CashierShift::query()->create(['store_id' => $store->id, 'user_id' => User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier])->id, 'status' => 'open', 'opened_at' => now(), 'opening_cash' => 0])->id;
    }
}
