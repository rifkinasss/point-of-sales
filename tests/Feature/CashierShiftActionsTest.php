<?php

namespace Tests\Feature;

use App\Actions\Shift\CloseShiftAction;
use App\Actions\Shift\OpenShiftAction;
use App\Enums\ShiftStatus;
use App\Models\User;
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
