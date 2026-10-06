<?php

namespace Tests\Feature;

use App\Enums\CashMovementType;
use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerTransactionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerOperationalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_browse_authorized_transactions_and_cash_without_numeric_urls(): void
    {
        [$owner, $store, $outside] = $this->context();
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier, 'is_active' => true]);
        $shift = CashierShift::withoutGlobalScopes()->create(['store_id' => $store->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Closed, 'opened_at' => now(), 'closed_at' => now(), 'opening_cash' => 100000, 'closing_cash_actual' => 125000, 'cash_difference' => 0]);
        $sale = Sale::withoutGlobalScopes()->create(['store_id' => $store->id, 'invoice_number' => 'OPS-OWNER-001', 'cashier_shift_id' => $shift->id, 'cashier_id' => $cashier->id, 'status' => SaleStatus::Paid, 'subtotal' => 25000, 'grand_total' => 25000, 'completed_at' => now()]);
        $outsideCashier = User::factory()->create(['store_id' => $outside->id, 'role' => UserRole::Cashier, 'is_active' => true]);
        $outsideShift = CashierShift::withoutGlobalScopes()->create(['store_id' => $outside->id, 'user_id' => $outsideCashier->id, 'status' => ShiftStatus::Closed, 'opened_at' => now(), 'closed_at' => now(), 'opening_cash' => 0]);
        Sale::withoutGlobalScopes()->create(['store_id' => $outside->id, 'invoice_number' => 'OPS-OUTSIDE-001', 'cashier_shift_id' => $outsideShift->id, 'cashier_id' => $outsideCashier->id, 'status' => SaleStatus::Paid, 'subtotal' => 99000, 'grand_total' => 99000, 'completed_at' => now()]);
        CashMovement::withoutGlobalScopes()->create(['store_id' => $store->id, 'shift_id' => $shift->id, 'user_id' => $cashier->id, 'type' => CashMovementType::Opening, 'amount' => 100000]);
        CashMovement::withoutGlobalScopes()->create(['store_id' => $store->id, 'shift_id' => $shift->id, 'user_id' => $cashier->id, 'type' => CashMovementType::Sale, 'amount' => 25000]);

        $this->actingAs($owner)->get(route('owner.transactions.index'))->assertOk()->assertSee($sale->invoice_number)->assertDontSee('OPS-OUTSIDE-001');
        $this->actingAs($owner)->get(route('owner.transactions.show', ['sale' => $sale->public_id]))->assertOk()->assertSee($sale->invoice_number);
        $this->actingAs($owner)->get('/owner/transactions/'.$sale->id)->assertNotFound();
        $this->actingAs($owner)->get(route('owner.cash-reconciliation.index'))->assertOk()->assertSee('Kas & Rekonsiliasi')->assertSee('Expected');
        $this->actingAs($owner)->get(route('owner.cash-reconciliation.show', ['shift' => $shift->public_id]))->assertOk()->assertSee($shift->public_id)->assertSee('Pergerakan kas');
    }

    public function test_non_owner_cannot_access_owner_operational_pages(): void
    {
        [$owner] = $this->context();
        $manager = User::factory()->create(['role' => UserRole::Manager, 'is_active' => true, 'store_id' => $owner->store_id]);
        $this->actingAs($manager)->get(route('owner.transactions.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('owner.cash-reconciliation.index'))->assertForbidden();
    }

    /** @return array{User,Store,Store} */
    private function context(): array
    {
        $store = Store::query()->create(['name' => 'Owner Operations', 'slug' => 'owner-operations-'.fake()->unique()->numberBetween(1, 999999)]);
        $outside = Store::query()->create(['name' => 'Outside Operations', 'slug' => 'outside-operations-'.fake()->unique()->numberBetween(1, 999999)]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $store->id]);
        $owner->workspaceStores()->sync([$store->id => ['is_default' => true]]);
        return [$owner, $store, $outside];
    }
}
