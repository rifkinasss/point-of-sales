<?php

namespace Tests\Feature;

use App\Actions\Admin\ManageUserAccessAction;
use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_users_and_create_manager_with_multiple_store_access(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        [$main, $branch] = $this->activeStores();

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();

        $manager = app(ManageUserAccessAction::class)->create($admin, [
            'name' => 'Platform Manager',
            'email' => 'platform-manager@example.test',
            'role' => UserRole::Manager->value,
            'is_active' => true,
            'password' => 'secure-password',
        ], [$main->public_id, $branch->public_id]);

        $this->assertSame(UserRole::Manager, $manager->role);
        $this->assertCount(2, $manager->managedStores);
        $this->assertTrue(Hash::check('secure-password', $manager->password));
        $this->assertDatabaseHas('audit_logs', ['event' => 'user_access_created', 'auditable_id' => $manager->id]);
        $this->actingAs($admin)->get('/admin/users/'.$manager->id.'/edit')->assertOk();
    }

    public function test_admin_can_edit_user_and_invalid_store_assignment_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        [$main] = $this->activeStores();
        $inactive = Store::query()->create(['name' => 'Closed Store', 'status' => StoreStatus::Inactive]);
        $owner = app(ManageUserAccessAction::class)->create($admin, [
            'name' => 'Platform Owner',
            'email' => 'platform-owner@example.test',
            'role' => UserRole::Owner->value,
            'is_active' => true,
        ], [$main->public_id]);

        app(ManageUserAccessAction::class)->update($admin, $owner, [
            'name' => 'Updated Owner',
            'email' => 'updated-owner@example.test',
            'role' => UserRole::Owner->value,
            'is_active' => true,
        ], [$main->public_id]);

        $this->assertSame('Updated Owner', $owner->refresh()->name);

        $this->expectException(ValidationException::class);
        app(ManageUserAccessAction::class)->update($admin, $owner->fresh(), [
            'name' => 'Updated Owner',
            'email' => 'updated-owner@example.test',
            'role' => UserRole::Owner->value,
            'is_active' => true,
        ], [$inactive->public_id]);
    }

    public function test_non_admin_cannot_access_user_resource_or_mutate_access(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $subject = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true]);

        $this->actingAs($owner)->get('/admin/users')->assertForbidden();
        $this->actingAs($owner)->get('/admin/users/create')->assertForbidden();
        $this->actingAs($owner)->get('/admin/users/'.$subject->id.'/edit')->assertForbidden();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(ManageUserAccessAction::class)->update($owner, $subject, [
            'name' => $subject->name,
            'email' => $subject->email,
            'role' => UserRole::Cashier->value,
            'is_active' => false,
        ], []);
    }

    public function test_cashier_with_open_shift_cannot_be_reassigned_or_deactivated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        [$main, $branch] = $this->activeStores();
        $cashier = app(ManageUserAccessAction::class)->create($admin, [
            'name' => 'Active Cashier',
            'email' => 'active-cashier@example.test',
            'role' => UserRole::Cashier->value,
            'is_active' => true,
        ], [$main->public_id]);
        CashierShift::withoutGlobalScopes()->create([
            'store_id' => $main->id,
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 0,
        ]);

        $this->expectException(ValidationException::class);
        app(ManageUserAccessAction::class)->update($admin, $cashier->fresh(), [
            'name' => 'Active Cashier',
            'email' => 'active-cashier@example.test',
            'role' => UserRole::Cashier->value,
            'is_active' => true,
        ], [$branch->public_id]);
    }

    public function test_last_active_admin_and_self_admin_are_protected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $this->expectException(ValidationException::class);
        app(ManageUserAccessAction::class)->update($admin, $admin, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Owner->value,
            'is_active' => false,
        ], []);
    }

    public function test_admin_role_cannot_be_created_through_assignable_role_rules(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);

        $this->expectException(ValidationException::class);
        app(ManageUserAccessAction::class)->create($admin, [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.test',
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
    }

    /** @return array{Store, Store} */
    private function activeStores(): array
    {
        return [
            Store::query()->create(['name' => 'Main Store', 'store_code' => 'MNS', 'status' => StoreStatus::Active]),
            Store::query()->create(['name' => 'Branch Store', 'store_code' => 'BRS', 'status' => StoreStatus::Active]),
        ];
    }
}
