<?php

namespace Tests\Feature;

use App\Actions\Shift\OpenShiftAction;
use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Livewire\Cashier\CloseShift;
use App\Livewire\Cashier\OpenShift;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashierOpenShiftTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_render_open_shift_page_without_active_shift(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)
            ->get(route('cashier.open-shift'))
            ->assertOk()
            ->assertSee('Mulai Shift Kasir')
            ->assertSee('Belum Dibuka');
    }

    public function test_valid_opening_cash_uses_action_and_redirects_to_pos(): void
    {
        $cashier = $this->cashier();

        $component = Livewire::actingAs($cashier)->test(OpenShift::class);

        $component
            ->set('openingCash', 100000)
            ->set('openingNote', 'Serah terima pagi')
            ->call('openShift')
            ->assertRedirect('/cashier/pos');

        $shift = CashierShift::query()->where('user_id', $cashier->id)->firstOrFail();

        $this->assertSame(ShiftStatus::Open, $shift->status);
        $this->assertSame(100000, $shift->opening_cash);
        $this->assertDatabaseHas('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Opening->value,
            'amount' => 100000,
        ]);
    }

    public function test_canonical_opening_cash_is_kept_as_integer_for_the_action(): void
    {
        $cashier = $this->cashier();

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->set('openingCash', 9000000)
            ->assertSet('openingCash', 9000000)
            ->call('openShift')
            ->assertRedirect('/cashier/pos');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'opening_cash' => 9000000,
        ]);

        $this->assertDatabaseHas('cash_movements', [
            'user_id' => $cashier->id,
            'amount' => 9000000,
        ]);
    }

    public function test_zero_opening_cash_is_accepted(): void
    {
        $cashier = $this->cashier();

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->set('openingCash', 0)
            ->call('openShift')
            ->assertRedirect('/cashier/pos');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'opening_cash' => 0,
            'status' => ShiftStatus::Open->value,
        ]);
    }

    public function test_opening_cash_defaults_to_zero_and_is_accepted(): void
    {
        $cashier = $this->cashier();

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->call('openShift')
            ->assertRedirect('/cashier/pos');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'opening_cash' => 0,
        ]);
    }

    public function test_cashier_with_active_shift_is_redirected_to_pos(): void
    {
        $cashier = $this->cashier();
        CashierShift::query()->create([
            'store_id' => $cashier->store_id,
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 100000,
        ]);

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->assertRedirect('/cashier/pos');
    }

    public function test_close_shift_workspace_opens_cash_movement_modal_without_changing_shift_rules(): void
    {
        $cashier = $this->cashier();
        app(OpenShiftAction::class)->handle($cashier, 100000);

        Livewire::actingAs($cashier)
            ->test(CloseShift::class)
            ->assertSet('movementModalOpen', false)
            ->call('openMovementModal', 'cash_out')
            ->assertSet('movementModalOpen', true)
            ->assertSet('movementType', 'cash_out')
            ->call('closeMovementModal')
            ->assertSet('movementModalOpen', false);
    }

    public function test_close_shift_cta_requires_valid_cash_and_note_for_variance(): void
    {
        $cashier = $this->cashier();
        app(OpenShiftAction::class)->handle($cashier, 100000);

        Livewire::actingAs($cashier)
            ->test(CloseShift::class)
            ->assertSet('canClose', false)
            ->set('closingCashActual', 100000)
            ->assertSet('variance', 0)
            ->assertSet('canClose', true)
            ->set('closingCashActual', 95000)
            ->assertSet('variance', -5000)
            ->assertSet('canClose', false)
            ->set('closingNote', 'Selisih kas dicatat untuk pemeriksaan supervisor.')
            ->assertSet('canClose', true);
    }

    public function test_close_shift_shows_success_snapshot_and_preserves_variance(): void
    {
        $cashier = $this->cashier();
        app(OpenShiftAction::class)->handle($cashier, 100000);

        Livewire::actingAs($cashier)
            ->test(CloseShift::class)
            ->set('closingCashActual', 103000)
            ->set('closingNote', 'Kas surplus dari penghitungan akhir.')
            ->call('closeShift')
            ->assertSet('closed', true)
            ->assertSet('closedShift.cash_difference', 3000)
            ->assertSee('Shift berhasil ditutup')
            ->assertSee('Lebih');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Closed->value,
            'expected_cash' => 100000,
            'closing_cash_actual' => 103000,
            'cash_difference' => 3000,
        ]);
    }

    public function test_close_shift_movement_list_uses_semantic_labels(): void
    {
        $cashier = $this->cashier();
        $shift = app(OpenShiftAction::class)->handle($cashier, 100000);
        app(\App\Actions\Cash\CreateCashInAction::class)->handle($shift, $cashier, 5000, 'Modal tambahan');
        app(\App\Actions\Cash\CreateCashOutAction::class)->handle($shift, $cashier, 2000, 'Pembelian kecil');

        Livewire::actingAs($cashier)
            ->test(CloseShift::class)
            ->assertSee('Kas awal')
            ->assertSee('Kas masuk')
            ->assertSee('Kas keluar')
            ->assertSee('Saldo awal shift')
            ->assertSee('aktivitas kas');
    }

    public function test_close_shift_logout_requires_confirmation_while_shift_is_active(): void
    {
        $cashier = $this->cashier();
        app(OpenShiftAction::class)->handle($cashier, 100000);

        Livewire::actingAs($cashier)
            ->test(CloseShift::class)
            ->call('requestLogout')
            ->assertSet('logoutConfirmationOpen', true)
            ->assertSee('Shift belum ditutup')
            ->call('closeLogoutConfirmation')
            ->assertSet('logoutConfirmationOpen', false);
    }

    public function test_non_cashier_cannot_open_shift(): void
    {
        $manager = $this->cashier(['role' => UserRole::Manager]);

        $this->actingAs($manager)
            ->get(route('cashier.open-shift'))
            ->assertForbidden();
    }

    /** @param array<string, mixed> $attributes */
    private function cashier(array $attributes = []): User
    {
        $store = Store::query()->create([
            'name' => 'Toko Uji',
            'slug' => 'toko-uji-'.fake()->unique()->numberBetween(1, 999999),
        ]);

        return User::factory()->create([
            'role' => UserRole::Cashier,
            'is_active' => true,
            'store_id' => $store->id,
            ...$attributes,
        ]);
    }
}
