<?php

namespace Tests\Feature;

use App\Actions\Shift\OpenShiftAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CentralLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_each_role_to_its_own_panel(): void
    {
        $paths = [
            UserRole::Admin->value => '/admin',
            UserRole::Owner->value => '/owner',
            UserRole::Manager->value => '/manager',
            UserRole::Cashier->value => '/cashier/open-shift',
        ];

        foreach ($paths as $role => $path) {
            $user = User::factory()->create([
                'email' => "{$role}@example.test",
                'password' => Hash::make('password'),
                'role' => $role,
            ]);

            $response = $this->post('/auth/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response->assertRedirect($path);
            $this->post('/auth/logout');
        }
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
            'role' => UserRole::Cashier,
            'is_active' => false,
        ]);

        $response = $this->from('/auth/login')->post('/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response
            ->assertRedirect('/auth/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cashier_with_an_open_shift_is_redirected_to_pos_after_login(): void
    {
        $cashier = User::factory()->create([
            'password' => Hash::make('password'),
            'role' => UserRole::Cashier,
        ]);
        app(OpenShiftAction::class)->handle($cashier, 100000);

        $this->post('/auth/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect('/cashier/pos-checkout');
    }

    public function test_cashier_cannot_log_out_while_a_shift_is_open(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::Cashier]);
        app(OpenShiftAction::class)->handle($cashier, 100000);

        $this->actingAs($cashier)
            ->post('/auth/logout')
            ->assertRedirect('/cashier/close-shift')
            ->assertSessionHas('shift_guard');

        $this->assertAuthenticatedAs($cashier);
    }
}
