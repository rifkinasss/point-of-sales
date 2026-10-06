<?php

namespace Tests\Feature;

use App\Actions\Owner\CreateStaffAction;
use App\Actions\Owner\UpdateStaffAction;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStaffQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerStaffManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_list_create_and_assign_manager_to_multiple_stores(): void
    {
        [$owner, $stores] = $this->ownerWithStores();

        $this->actingAs($owner)->get(route('owner.staff.index'))->assertOk()->assertSee('Manager & Staff');
        $manager = app(CreateStaffAction::class)->execute($owner, [
            'name' => 'Multi Store Manager', 'email' => 'multi@example.test', 'role' => 'manager', 'is_active' => true,
        ], [$stores[0]->public_id, $stores[1]->public_id]);

        $this->assertSame(UserRole::Manager, $manager->role);
        $this->assertCount(2, $manager->workspaceStores);
        $this->assertTrue(app(ActiveStoreContext::class)->availableStores($manager)->pluck('id')->contains($stores[1]->id));
        $this->assertTrue($manager->public_id !== (string) $manager->id);

        $this->actingAs($owner)->get(route('owner.staff.show', ['user' => $manager->public_id]))
            ->assertOk()->assertSee('Multi Store Manager')->assertSee('2 toko');
        $this->actingAs($owner)->get(route('owner.staff.show', ['user' => (string) $manager->id]))->assertNotFound();
    }

    public function test_owner_can_create_cashier_with_one_operational_store(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        $cashier = app(CreateStaffAction::class)->execute($owner, [
            'name' => 'Store Cashier', 'email' => 'cashier@example.test', 'role' => 'cashier', 'is_active' => true,
        ], [$stores[1]->public_id]);

        $this->assertSame(UserRole::Cashier, $cashier->role);
        $this->assertSame($stores[1]->id, $cashier->store_id);
        $this->assertCount(0, $cashier->workspaceStores);
    }

    public function test_owner_cannot_assign_unauthorized_store_or_owner_role(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        $outside = Store::query()->create(['name' => 'Outside', 'store_code' => 'OUT']);

        $this->expectException(ValidationException::class);
        app(CreateStaffAction::class)->execute($owner, [
            'name' => 'Invalid', 'email' => 'invalid@example.test', 'role' => 'manager', 'is_active' => true,
        ], [$outside->public_id]);
    }

    public function test_duplicate_email_is_rejected_by_owner_form(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        User::factory()->create(['email' => 'duplicate@example.test', 'role' => UserRole::Cashier, 'store_id' => $stores[0]->id]);

        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Staff\Form::class)
            ->set('name', 'Duplicate')
            ->set('email', 'duplicate@example.test')
            ->set('role', 'cashier')
            ->set('selectedStorePublicIds', [$stores[0]->public_id])
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_owner_can_edit_and_toggle_user_but_active_shift_blocks_dangerous_changes(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        $cashier = app(CreateStaffAction::class)->execute($owner, [
            'name' => 'Editable Cashier', 'email' => 'editable@example.test', 'role' => 'cashier', 'is_active' => true,
        ], [$stores[0]->public_id]);
        app(UpdateStaffAction::class)->execute($owner, $cashier, [
            'name' => 'Updated Cashier', 'email' => 'updated@example.test', 'role' => 'cashier', 'is_active' => true,
        ], [$stores[1]->public_id]);
        $this->assertSame($stores[1]->id, $cashier->refresh()->store_id);

        CashierShift::withoutGlobalScopes()->create([
            'store_id' => $stores[1]->id, 'user_id' => $cashier->id, 'status' => ShiftStatus::Open,
            'opened_at' => now(), 'opening_cash' => 0,
        ]);
        $this->expectException(ValidationException::class);
        app(UpdateStaffAction::class)->execute($owner, $cashier->fresh(), [
            'name' => 'Updated Cashier', 'email' => 'updated@example.test', 'role' => 'cashier', 'is_active' => true,
        ], [$stores[0]->public_id]);
    }

    public function test_inactive_user_cannot_access_manager_workspace_and_reset_flow_uses_existing_broker(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        $manager = app(CreateStaffAction::class)->execute($owner, [
            'name' => 'Inactive Manager', 'email' => 'inactive@example.test', 'role' => 'manager', 'is_active' => false,
        ], [$stores[0]->public_id]);
        $this->actingAs($manager)->get(route('manager.dashboard'))->assertForbidden();

        Notification::fake();
        $manager->forceFill(['is_active' => true])->save();
        $this->actingAs($owner)->get(route('owner.staff.show', ['user' => $manager->public_id]))->assertOk();
        Livewire::actingAs($owner)->test(\App\Livewire\Owner\Staff\Show::class, ['user' => $manager])
            ->call('sendPasswordReset');
        Notification::assertSentTo($manager->fresh(), \Illuminate\Auth\Notifications\ResetPassword::class);
    }

    public function test_owner_cannot_view_another_owners_staff(): void
    {
        [$owner, $stores] = $this->ownerWithStores();
        $otherStore = Store::query()->create(['name' => 'Other Store', 'store_code' => 'OTH']);
        $otherOwner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $otherStore->id]);
        $otherOwner->workspaceStores()->sync([$otherStore->id => ['is_default' => true]]);
        $otherManager = app(CreateStaffAction::class)->execute($otherOwner, [
            'name' => 'Other Manager', 'email' => 'other-manager@example.test', 'role' => 'manager', 'is_active' => true,
        ], [$otherStore->public_id]);

        $this->actingAs($owner)->get(route('owner.staff.show', ['user' => $otherManager->public_id]))->assertNotFound();
        $this->assertNotEmpty($stores);
    }

    /** @return array{User, array<int, Store>} */
    private function ownerWithStores(): array
    {
        $stores = [
            Store::query()->create(['name' => 'Owner Main', 'store_code' => 'OWN']),
            Store::query()->create(['name' => 'Owner Branch', 'store_code' => 'BRN']),
        ];
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $stores[0]->id]);
        $owner->workspaceStores()->sync([
            $stores[0]->id => ['is_default' => true],
            $stores[1]->id => ['is_default' => false],
        ]);
        return [$owner, $stores];
    }
}
