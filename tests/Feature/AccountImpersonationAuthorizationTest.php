<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
