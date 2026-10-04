<?php

namespace Tests\Feature;

use App\Enums\CashMovementType;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
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
            ->set('openingCash', '100.000')
            ->set('openingNote', 'Serah terima pagi')
            ->call('openShift')
            ->assertRedirect('/cashier/pos-checkout');

        $shift = CashierShift::query()->where('user_id', $cashier->id)->firstOrFail();

        $this->assertSame(ShiftStatus::Open, $shift->status);
        $this->assertSame(100000, $shift->opening_cash);
        $this->assertDatabaseHas('cash_movements', [
            'shift_id' => $shift->id,
            'type' => CashMovementType::Opening->value,
            'amount' => 100000,
        ]);
    }

    public function test_zero_opening_cash_is_accepted(): void
    {
        $cashier = $this->cashier();

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->set('openingCash', '0')
            ->call('openShift')
            ->assertRedirect('/cashier/pos-checkout');

        $this->assertDatabaseHas('cashier_shifts', [
            'user_id' => $cashier->id,
            'opening_cash' => 0,
            'status' => ShiftStatus::Open->value,
        ]);
    }

    public function test_invalid_opening_cash_is_rejected_without_creating_shift(): void
    {
        $cashier = $this->cashier();

        Livewire::actingAs($cashier)
            ->test(OpenShift::class)
            ->set('openingCash', 'bukan angka')
            ->call('openShift')
            ->assertHasErrors(['openingCash']);

        $this->assertDatabaseCount('cashier_shifts', 0);
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
            ->assertRedirect('/cashier/pos-checkout');
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
