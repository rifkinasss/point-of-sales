<?php

namespace Tests\Feature;

use App\Actions\Store\SetStoreStatusAction;
use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminStoreAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_inspect_store_list_and_detail_without_creation_or_edit_routes(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $store = Store::query()->create(['name' => 'Platform Store', 'store_code' => 'PLT']);

        $this->actingAs($admin)->get('/admin/stores')
            ->assertOk()
            ->assertSee('Platform Store');
        $this->actingAs($admin)->get('/admin/stores/'.$store->id)->assertOk();
        $this->actingAs($admin)->get('/admin/stores/create')->assertNotFound();
        $this->actingAs($admin)->get('/admin/stores/'.$store->id.'/edit')->assertNotFound();
    }

    public function test_only_active_admin_can_access_platform_store_administration(): void
    {
        foreach ([UserRole::Owner, UserRole::Manager, UserRole::Cashier] as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->actingAs($user)->get('/admin/stores')->assertForbidden();
        }

        $inactiveAdmin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => false]);
        $this->actingAs($inactiveAdmin)->get('/admin/stores')->assertForbidden();
    }

    public function test_deactivation_is_rejected_while_a_cashier_shift_is_open(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $cashier = User::factory()->create(['role' => UserRole::Cashier, 'is_active' => true]);
        $store = Store::query()->create(['name' => 'Busy Store', 'store_code' => 'BUS']);
        $shift = CashierShift::withoutGlobalScopes()->create([
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'status' => ShiftStatus::Open,
            'opened_at' => now(),
            'opening_cash' => 100000,
        ]);

        try {
            app(SetStoreStatusAction::class)->execute($admin, $store, StoreStatus::Inactive);
            $this->fail('Expected active shift validation to reject deactivation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('store', $exception->errors());
        }

        $this->assertSame(StoreStatus::Active, $store->refresh()->status);
        $this->assertSame(ShiftStatus::Open, $shift->refresh()->status);
    }

    public function test_admin_can_deactivate_store_without_mutating_operational_identity(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $store = Store::query()->create(['name' => 'Safe Store', 'store_code' => 'SAFE', 'branch_code' => 'MAIN']);
        $publicId = $store->public_id;

        $updated = app(SetStoreStatusAction::class)->execute($admin, $store, StoreStatus::Inactive);

        $this->assertSame(StoreStatus::Inactive, $updated->status);
        $this->assertSame($publicId, $updated->public_id);
        $this->assertSame('SAFE', $updated->store_code);
        $this->assertSame('MAIN', $updated->branch_code);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('cashier_shifts', 0);
    }

    public function test_owner_can_only_change_status_for_a_store_in_their_workspace(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true]);
        $ownedStore = Store::query()->create(['name' => 'Owned Store', 'store_code' => 'OWN']);
        $outsideStore = Store::query()->create(['name' => 'Outside Store', 'store_code' => 'OUT']);
        $owner->workspaceStores()->attach($ownedStore->id, ['is_default' => true]);

        app(SetStoreStatusAction::class)->execute($owner, $ownedStore, StoreStatus::Inactive);
        $this->assertSame(StoreStatus::Inactive, $ownedStore->refresh()->status);

        try {
            app(SetStoreStatusAction::class)->execute($owner, $outsideStore, StoreStatus::Inactive);
            $this->fail('Expected an unauthorized store to return not found.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }
}
