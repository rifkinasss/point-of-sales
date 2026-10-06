<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_canonical_login(): void
    {
        $this->get(route('owner.dashboard'))->assertRedirect(route('auth.login'));
    }

    public function test_owner_defaults_to_all_authorized_stores(): void
    {
        [$owner, $primary, $branch] = $this->ownerWithStores();

        $this->actingAs($owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Owner')
            ->assertSee('Semua Cabang')
            ->assertSee($primary->name)
            ->assertSee($branch->name);

        $scope = app(ActiveStoreContext::class)->scope($owner);
        $this->assertTrue($scope->all);
        $this->assertEqualsCanonicalizing([$primary->id, $branch->id], $scope->ids());
    }

    public function test_owner_can_switch_only_to_authorized_public_store_id(): void
    {
        [$owner, $primary, $branch, $outside] = $this->ownerWithStores();

        $this->actingAs($owner)->post(route('owner.store-context.switch'), ['store' => $branch->public_id])
            ->assertRedirect();
        $this->assertSame($branch->public_id, app(ActiveStoreContext::class)->identifier($owner));

        $this->actingAs($owner)->post(route('owner.store-context.switch'), ['store' => $outside->public_id])
            ->assertForbidden();
        $this->actingAs($owner)->post(route('owner.store-context.switch'), ['store' => (string) $primary->id])
            ->assertForbidden();
    }

    public function test_owner_selector_does_not_expose_numeric_store_ids(): void
    {
        [$owner, $primary] = $this->ownerWithStores();

        $this->actingAs($owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee($primary->public_id)
            ->assertDontSee('value="'.$primary->id.'"');
    }

    public function test_owner_store_scope_is_enforced_when_legacy_store_id_is_empty(): void
    {
        [$owner, $primary, $branch, $outside] = $this->ownerWithStores();
        $owner->forceFill(['store_id' => null])->save();

        Customer::withoutGlobalScopes()->create([
            'store_id' => $primary->id,
            'code' => 'PRIMARY-CUSTOMER',
            'name' => 'Authorized Customer',
            'is_active' => true,
        ]);
        Customer::withoutGlobalScopes()->create([
            'store_id' => $outside->id,
            'code' => 'OUTSIDE-CUSTOMER',
            'name' => 'Unauthorized Customer',
            'is_active' => true,
        ]);

        $this->actingAs($owner);

        $this->assertSame(
            ['Authorized Customer'],
            Customer::query()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_owner_dashboard_aggregates_paid_sales_and_completed_payments_by_context(): void
    {
        [$owner, $primary, $branch] = $this->ownerWithStores();
        $cashier = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true, 'store_id' => $primary->id]);
        $now = now();
        $shift = CashierShift::withoutGlobalScopes()->create([
            'store_id' => $primary->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Open,
            'opened_at' => $now, 'opening_cash' => 0,
        ]);

        $primarySale = Sale::withoutGlobalScopes()->create([
            'store_id' => $primary->id, 'invoice_number' => 'OWN-PRIMARY-001', 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id,
            'status' => SaleStatus::Paid, 'subtotal' => 100000, 'item_discount_total' => 5000,
            'promotion_discount_total' => 0, 'transaction_discount_total' => 0, 'grand_total' => 95000,
            'completed_at' => $now,
        ]);
        Payment::query()->create([
            'sale_id' => $primarySale->id, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Completed,
            'amount_due' => 95000, 'amount_received' => 100000, 'change_amount' => 5000, 'paid_at' => $now, 'received_by' => $cashier->id,
        ]);

        $branchSale = Sale::withoutGlobalScopes()->create([
            'store_id' => $branch->id, 'invoice_number' => 'OWN-BRANCH-001', 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id,
            'status' => SaleStatus::Paid, 'subtotal' => 50000, 'item_discount_total' => 0,
            'promotion_discount_total' => 0, 'transaction_discount_total' => 0, 'grand_total' => 50000,
            'completed_at' => $now,
        ]);
        Payment::query()->create([
            'sale_id' => $branchSale->id, 'method' => PaymentMethod::Qris, 'status' => PaymentStatus::Completed,
            'amount_due' => 50000, 'amount_received' => 50000, 'change_amount' => 0, 'paid_at' => $now, 'received_by' => $cashier->id,
        ]);
        Payment::query()->create([
            'sale_id' => $primarySale->id, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Pending,
            'amount_due' => 900000, 'amount_received' => 900000, 'change_amount' => 0, 'paid_at' => $now, 'received_by' => $cashier->id,
        ]);
        Payment::query()->create([
            'sale_id' => $primarySale->id, 'method' => PaymentMethod::Cash, 'status' => PaymentStatus::Refunded,
            'amount_due' => 5000, 'amount_received' => 5000, 'change_amount' => 0, 'paid_at' => $now,
            'refunded_at' => $now, 'received_by' => $cashier->id,
        ]);

        $dashboard = app(\App\Queries\OwnerDashboardQuery::class)->dashboard($owner);

        $this->assertSame(2, $dashboard['metrics']['transactions']);
        $this->assertSame(140000, $dashboard['metrics']['netSales']);
        $this->assertSame(5000, $dashboard['metrics']['refunds']);
        $this->assertSame(95000, $dashboard['paymentBreakdown']['cash']);
        $this->assertSame(50000, $dashboard['paymentBreakdown']['qris']);

        $this->actingAs($owner)->post(route('owner.store-context.switch'), ['store' => $branch->public_id]);
        $branchDashboard = app(\App\Queries\OwnerDashboardQuery::class)->dashboard($owner->fresh());

        $this->assertSame(1, $branchDashboard['metrics']['transactions']);
        $this->assertSame(50000, $branchDashboard['metrics']['netSales']);
    }

    /** @return array{User, Store, Store, Store} */
    private function ownerWithStores(): array
    {
        $primary = Store::query()->create(['name' => 'Owner Primary', 'slug' => 'owner-primary-'.fake()->unique()->numberBetween(1, 999999)]);
        $branch = Store::query()->create(['name' => 'Owner Branch', 'slug' => 'owner-branch-'.fake()->unique()->numberBetween(1, 999999)]);
        $outside = Store::query()->create(['name' => 'Owner Outside', 'slug' => 'owner-outside-'.fake()->unique()->numberBetween(1, 999999)]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $primary->id]);
        $owner->workspaceStores()->sync([
            $primary->id => ['is_default' => true],
            $branch->id => ['is_default' => false],
        ]);

        return [$owner, $primary, $branch, $outside];
    }
}
