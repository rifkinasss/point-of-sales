<?php

namespace Tests\Feature;

use App\Actions\Cash\CreateCashInAction;
use App\Actions\Cash\CreateCashOutAction;
use App\Actions\Shift\CloseShiftAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Models\User;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CashierShiftActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_only_have_one_open_shift(): void
    {
        $cashier = User::factory()->create();

        app(OpenShiftAction::class)->handle($cashier, 100000, 'Kas awal');

        $this->expectException(ValidationException::class);

        app(OpenShiftAction::class)->handle($cashier, 0);
    }

    public function test_opening_shift_creates_opening_cash_movement(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000, 'Kas awal');

        $this->assertDatabaseHas('cash_movements', [
            'shift_id' => $shift->id,
            'user_id' => $cashier->id,
            'type' => CashMovementType::Opening->value,
            'amount' => 100000,
        ]);
    }

    public function test_cash_in_and_cash_out_are_reflected_in_shift_reconciliation(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);

        app(CreateCashInAction::class)->handle($shift, $cashier, 25000, 'Modal tambahan');
        app(CreateCashOutAction::class)->handle($shift, $cashier, 10000, 'Pembelian kecil');

        $closedShift = app(CloseShiftAction::class)->handle($shift, $cashier, 115000);

        $this->assertSame(115000, $closedShift->expected_cash);
        $this->assertSame(0, $closedShift->cash_difference);
        $this->assertDatabaseHas('cash_movements', ['shift_id' => $shift->id, 'type' => CashMovementType::CashIn->value, 'amount' => 25000]);
        $this->assertDatabaseHas('cash_movements', ['shift_id' => $shift->id, 'type' => CashMovementType::CashOut->value, 'amount' => 10000]);
    }

    public function test_cash_movements_require_active_owned_shift_and_positive_amount(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);

        $this->expectException(ValidationException::class);
        app(CreateCashOutAction::class)->handle($shift, $cashier, 0, 'Tidak valid');
    }

    public function test_cash_movement_cannot_use_another_cashiers_or_stores_shift(): void
    {
        $store = Store::query()->create(['name' => 'Toko kas uji', 'slug' => 'toko-kas-uji']);
        $owner = User::factory()->create(['store_id' => $store->id]);
        $otherStore = Store::query()->create(['name' => 'Toko lain kas', 'slug' => 'toko-lain-kas']);
        $otherUser = User::factory()->create(['store_id' => $otherStore->id]);
        $shift = app(OpenShiftAction::class)->handle($owner, 100000);

        $this->expectException(ValidationException::class);
        app(CreateCashInAction::class)->handle($shift, $otherUser, 1000, 'Cross-store');
    }

    public function test_closed_shift_cannot_be_closed_again(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);
        app(CloseShiftAction::class)->handle($shift, $cashier, 100000);

        $this->expectException(ValidationException::class);
        app(CloseShiftAction::class)->handle($shift, $cashier, 100000);
    }

    public function test_closing_shift_calculates_cash_difference(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);

        $closedShift = app(CloseShiftAction::class)->handle($shift, $cashier, 100000);

        $this->assertSame(ShiftStatus::Closed, $closedShift->status);
        $this->assertSame(100000, $closedShift->expected_cash);
        $this->assertSame(0, $closedShift->cash_difference);
    }

    public function test_closing_shift_with_difference_requires_note(): void
    {
        $cashier = User::factory()->create();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);

        $this->expectException(ValidationException::class);

        app(CloseShiftAction::class)->handle($shift, $cashier, 95000);
    }
}
