<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManagerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_settings_and_update_profile(): void
    {
        [$manager] = $this->context();

        $this->actingAs($manager)->get(route('manager.settings'))->assertOk()->assertSee('Pengaturan Akun')->assertSee('Profil');

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Settings::class, ['section' => 'profile'])
            ->set('name', 'Manager Baru')
            ->set('email', 'manager.baru@example.test')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $manager->id, 'name' => 'Manager Baru', 'email' => 'manager.baru@example.test']);
    }

    public function test_profile_email_must_be_unique_and_cashier_cannot_access_settings(): void
    {
        [$manager] = $this->context();
        $other = User::factory()->create(['email' => 'taken@example.test']);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Settings::class)
            ->set('email', $other->email)
            ->call('saveProfile')
            ->assertHasErrors(['email' => 'unique']);

        $cashier = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true]);
        $this->actingAs($cashier)->get(route('manager.settings'))->assertForbidden();
    }

    public function test_manager_can_change_password_without_leaking_password_values(): void
    {
        [$manager] = $this->context();

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'new-password-123')
            ->call('changePassword')
            ->assertHasNoErrors()
            ->assertDontSee('new-password-123');

        $manager->refresh();
        $this->assertTrue(Hash::check('new-password-123', $manager->password));
        $this->assertFalse(Hash::check('password', $manager->password));
    }

    public function test_wrong_current_password_and_mismatched_confirmation_are_rejected(): void
    {
        [$manager] = $this->context();

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'new-password-123')
            ->call('changePassword')
            ->assertHasErrors(['currentPassword']);

        Livewire::actingAs($manager)->test(\App\Livewire\Manager\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'different-password')
            ->call('changePassword')
            ->assertHasErrors(['newPassword' => 'confirmed']);
    }

    public function test_access_tab_shows_only_assigned_stores_as_read_only(): void
    {
        [$manager, $primary, $branch] = $this->context();
        $outside = Store::query()->create(['name' => 'Outside Store', 'slug' => 'outside-store']);

        $manager->managedStores()->sync([
            $primary->id => ['is_default' => true],
            $branch->id => ['is_default' => false],
        ]);

        $this->actingAs($manager)->get(route('manager.settings.access'))
            ->assertOk()
            ->assertSee($primary->name)
            ->assertSee($branch->name)
            ->assertDontSee($outside->name)
            ->assertSee('Akses cabang dikelola oleh Owner atau Administrator.');
    }

    /** @return array{User, Store, Store} */
    private function context(): array
    {
        $primary = Store::query()->create(['name' => 'Primary Store', 'slug' => 'primary-settings-'.fake()->unique()->numberBetween(1, 999999)]);
        $branch = Store::query()->create(['name' => 'Branch Store', 'slug' => 'branch-settings-'.fake()->unique()->numberBetween(1, 999999)]);
        $manager = User::factory()->create([
            'role' => UserRole::Manager,
            'is_active' => true,
            'store_id' => $primary->id,
        ]);

        return [$manager, $primary, $branch];
    }
}
