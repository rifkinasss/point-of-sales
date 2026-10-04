<?php

namespace Tests\Feature;

use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\CashMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerShiftsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_monitor_store_scoped_shifts_with_public_identifier(): void
    {
        [$manager, $cashier, $store] = $this->context('m3-monitor');
        $shift = $this->shift($cashier, $store, ShiftStatus::Closed, 100000, 125000, 120000, -5000);
        CashMovement::query()->create(['store_id' => $store->id, 'shift_id' => $shift->id, 'user_id' => $cashier->id, 'type' => CashMovementType::Opening, 'amount' => 100000]);
        CashMovement::query()->create(['store_id' => $store->id, 'shift_id' => $shift->id, 'user_id' => $cashier->id, 'type' => CashMovementType::Sale, 'amount' => 25000]);

        $this->actingAs($manager)->get(route('manager.shifts.index'))->assertOk()->assertSee('Shift kasir')->assertSee($cashier->name);
        $this->actingAs($manager)->get(route('manager.shifts.show', ['shift' => $shift->public_id]))->assertOk()->assertSee('Timeline pergerakan kas');
        $this->actingAs($manager)->get('/manager/shifts/'.$shift->id)->assertNotFound();
        $this->assertSame(125000, app(\App\Services\CashBalanceCalculator::class)->summary($shift)['expected']);
    }

    public function test_cashier_and_other_store_cannot_access_manager_shift_workspace(): void
    {
        [$manager, $cashier, $store] = $this->context('m3-isolation');
        $shift = $this->shift($cashier, $store, ShiftStatus::Open, 50000);
        $otherStore = Store::query()->create(['name' => 'Other M3', 'slug' => 'other-m3']);
        $otherShift = $this->shift(User::factory()->create(['store_id' => $otherStore->id, 'role' => UserRole::Cashier, 'is_active' => true]), $otherStore, ShiftStatus::Open, 50000);

        $this->actingAs($cashier)->get(route('manager.shifts.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('manager.shifts.show', ['shift' => $otherShift->public_id]))->assertNotFound();
        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Shifts\Show::class, ['shift' => $shift->public_id])->assertStatus(200);
    }

    public function test_manager_cash_workspace_paginates_shift_reconciliation(): void
    {
        [$manager, $cashier, $store] = $this->context('m3-pagination');
        for ($index = 0; $index < 21; $index++) {
            $this->shift($cashier, $store, ShiftStatus::Closed, 10000, 10000, 10000, 0);
        }

        $this->actingAs($manager)->get(route('manager.cash.index'))->assertOk()->assertSee('Kas & Rekonsiliasi');
        $this->assertSame(21, app(\App\Queries\ManagerShiftQuery::class)->paginate($manager, ['datePreset' => 'today', 'perPage' => 20])->total());
    }

    /** @return array{User, User, Store} */
    private function context(string $slug): array
    {
        $store = Store::query()->create(['name' => 'Store '.$slug, 'slug' => $slug]);
        $manager = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Manager, 'is_active' => true]);
        $cashier = User::factory()->create(['store_id' => $store->id, 'role' => UserRole::Cashier, 'is_active' => true]);

        return [$manager, $cashier, $store];
    }

    private function shift(User $cashier, Store $store, ShiftStatus $status, int $opening, ?int $expected = null, ?int $actual = null, ?int $difference = null): CashierShift
    {
        return CashierShift::query()->create([
            'store_id' => $store->id, 'user_id' => $cashier->id, 'status' => $status,
            'opened_at' => now(), 'opening_cash' => $opening, 'closed_at' => $status === ShiftStatus::Closed ? now() : null,
            'expected_cash' => $expected, 'closing_cash_actual' => $actual, 'cash_difference' => $difference,
        ]);
    }
}
