<?php

namespace Tests\Feature;

use App\Actions\Owner\UpdateOwnerProfileAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_access_settings_and_update_profile_without_escalating(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $this->actingAs($owner)->get(route('owner.settings.index'))->assertOk()->assertSee('Pengaturan Akun')->assertSee('Profil');

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Settings::class)
            ->set('name', 'Owner Baru')->set('email', 'owner.baru@example.test')->call('saveProfile')->assertHasNoErrors();

        $owner->refresh();
        $this->assertSame('Owner Baru', $owner->name);
        $this->assertSame('owner.baru@example.test', $owner->email);
        $this->assertSame(UserRole::Owner, $owner->role);
    }

    public function test_non_owner_cannot_access_owner_settings(): void
    {
        foreach ([UserRole::Admin, UserRole::Manager, UserRole::Cashier] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($user)->get(route('owner.settings.index'))->assertForbidden();
        }
    }

    public function test_guest_cannot_access_owner_settings(): void
    {
        $this->get(route('owner.settings.index'))->assertRedirect(route('auth.login'));
    }

    public function test_owner_password_change_requires_current_password_and_hashes_new_password(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'password' => Hash::make('password'), 'is_active' => true]);

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'wrong-password')->set('newPassword', 'new-password-123')->set('newPasswordConfirmation', 'new-password-123')->call('changePassword')->assertHasErrors(['currentPassword']);

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'password')->set('newPassword', 'new-password-123')->set('newPasswordConfirmation', 'new-password-123')->call('changePassword')->assertHasNoErrors()->assertDontSee('new-password-123');

        $owner->refresh();
        $this->assertTrue(Hash::check('new-password-123', $owner->password));
        $this->assertFalse(Hash::check('password', $owner->password));
    }

    public function test_profile_email_validation_and_security_routes_are_available(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $other = User::factory()->create(['email' => 'taken-owner@example.test']);

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Settings::class)
            ->set('email', $other->email)->call('saveProfile')->assertHasErrors(['email' => 'unique']);

        $this->actingAs($owner)->get(route('owner.settings.security'))->assertOk()->assertSee('Keamanan');
        $this->actingAs($owner)->get(route('owner.settings.regional'))->assertOk()->assertSee('Timezone tampilan');
    }

    public function test_password_confirmation_is_required(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Owner,
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Settings::class, ['section' => 'security'])
            ->set('currentPassword', 'password')
            ->set('newPassword', 'new-password-123')
            ->set('newPasswordConfirmation', 'different-password-123')
            ->call('changePassword')
            ->assertHasErrors(['newPassword' => 'confirmed']);

        $owner->refresh();
        $this->assertTrue(Hash::check('password', $owner->password));
    }

    public function test_profile_action_ignores_role_store_and_identifier_fields(): void
    {
        $owner = User::factory()->create([
            'role' => UserRole::Owner,
            'is_active' => true,
            'name' => 'Owner Lama',
            'email' => 'owner-lama@example.test',
        ]);

        app(UpdateOwnerProfileAction::class)->execute($owner, [
            'name' => 'Owner Baru',
            'email' => 'owner-baru@example.test',
            'role' => UserRole::Admin->value,
            'store_id' => 999999,
            'id' => 999999,
        ]);

        $owner->refresh();

        $this->assertSame('Owner Baru', $owner->name);
        $this->assertSame('owner-baru@example.test', $owner->email);
        $this->assertSame(UserRole::Owner, $owner->role);
        $this->assertNull($owner->store_id);
        $this->assertNotSame(999999, $owner->getKey());
    }

    public function test_owner_logout_invalidates_session_and_protects_owner_routes(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        $this->actingAs($owner)
            ->post(route('auth.logout'))
            ->assertRedirect(route('auth.login'));

        $this->assertGuest();
        $this->get(route('owner.dashboard'))->assertRedirect(route('auth.login'));
    }
}
