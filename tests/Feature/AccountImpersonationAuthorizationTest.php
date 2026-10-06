<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Packstub\AccountSwitcher\AccountSwitcher;
use Packstub\AccountSwitcher\Enums\SwitchReason;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Tests\TestCase;

class AccountImpersonationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_owner_manager_and_cashier_but_not_another_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->assertTrue($admin->canImpersonate(User::factory()->create(['role' => UserRole::Owner, 'is_active' => true])));
        $this->assertTrue($admin->canImpersonate(User::factory()->create(['role' => UserRole::Manager, 'is_active' => true])));
        $this->assertTrue($admin->canImpersonate(User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true])));
        $this->assertFalse($admin->canImpersonate(User::factory()->create(['role' => UserRole::Admin, 'is_active' => true])));
    }

    public function test_owner_can_only_impersonate_manager_and_cashier(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        $this->assertTrue($owner->canImpersonate(User::factory()->create(['role' => UserRole::Manager, 'is_active' => true])));
        $this->assertTrue($owner->canImpersonate(User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true])));
        $this->assertFalse($owner->canImpersonate(User::factory()->create(['role' => UserRole::Owner, 'is_active' => true])));
        $this->assertFalse($owner->canImpersonate(User::factory()->create(['role' => UserRole::Admin, 'is_active' => true])));
    }

    public function test_inactive_users_cannot_be_impersonated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $inactiveCashier = User::factory()->create([
            'role' => UserRole::Cashier,
            'is_active' => false,
        ]);

        $this->assertFalse($admin->canImpersonate($inactiveCashier));
        $this->assertFalse($inactiveCashier->canBeImpersonated($admin));
    }

    public function test_account_switcher_uses_target_role_redirects_and_records_audit(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'is_active' => true]);
        $cashier = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true]);

        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);
        $switcher = app(AccountSwitcher::class);

        $this->assertSame(route('owner.dashboard'), $this->switchAndRestore($switcher, $owner, $admin));
        $this->assertSame(route('manager.dashboard'), $this->switchAndRestore($switcher, $manager, $admin));
        $this->assertSame(route('cashier.open-shift'), $this->switchAndRestore($switcher, $cashier, $admin));

        $store = Store::create(['name' => 'Switch Store', 'status' => 'active']);
        CashierShift::create([
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 0,
        ]);

        $this->assertSame(route('cashier.pos'), $this->switchAndRestore($switcher, $cashier, $admin));
        $this->assertSame(4, AccountSwitch::query()->where('from_user_id', $admin->id)->where('reason', SwitchReason::Impersonation)->count());
        $this->assertSame(4, AccountSwitch::query()->where('to_user_id', $admin->id)->where('reason', SwitchReason::ImpersonationEnded)->count());
    }

    public function test_account_switcher_rejects_non_admin_actor_and_admin_target(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $secondAdmin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'is_active' => true]);

        Filament::setCurrentPanel('admin');
        $switcher = app(AccountSwitcher::class);

        $this->assertFalse($switcher->canImpersonate($admin, $secondAdmin));
        $this->assertFalse($switcher->canImpersonate($manager, $owner));
    }

    public function test_switch_back_endpoint_restores_admin_without_logout_and_redirects_to_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);
        app(AccountSwitcher::class)->impersonate($owner, $admin);

        $response = $this->post(route('account-switcher.switch-back'));

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        $this->assertFalse(app(AccountSwitcher::class)->isImpersonating());
    }

    public function test_target_workspace_shows_persistent_switch_back_banner(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);
        app(AccountSwitcher::class)->impersonate($owner, $admin);

        $this->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Anda sedang menggunakan akun Owner')
            ->assertSee('Kembali ke Administrator')
            ->assertSee(route('account-switcher.switch-back'), false);
    }

    public function test_switch_back_does_not_restore_an_inactive_original_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);

        Filament::setCurrentPanel('admin');
        $this->actingAs($admin);
        app(AccountSwitcher::class)->impersonate($owner, $admin);
        $admin->update(['is_active' => false]);

        $response = $this->post(route('account-switcher.switch-back'));

        $response->assertRedirect(route('auth.login'));
        $this->assertGuest();
        $this->assertFalse(app(AccountSwitcher::class)->isImpersonating());
    }

    private function switchAndRestore(AccountSwitcher $switcher, User $target, User $admin): string
    {
        $switcher->impersonate($target, $admin);

        $this->assertAuthenticatedAs($target);
        $redirect = $switcher->redirectUrlFor($target);
        $this->assertTrue($switcher->isImpersonating());

        $switcher->stopImpersonating();
        $this->assertAuthenticatedAs($admin);

        return $redirect;
    }
}
