<?php

namespace Tests\Feature;

use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Livewire\Owner\Stores\Form;
use App\Livewire\Owner\Stores\Index;
use App\Models\InvoiceSequence;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class OwnerStoreManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_only_sees_authorized_stores_and_routes_use_public_id(): void
    {
        [$owner, $primary, $outside] = $this->ownerWithStore();

        $this->actingAs($owner)->get(route('owner.stores.index'))
            ->assertOk()
            ->assertSee($primary->name)
            ->assertDontSee($outside->name)
            ->assertSee($primary->public_id)
            ->assertDontSee('value="'.$primary->id.'"');

        $this->actingAs($owner)->get(route('owner.stores.show', ['store' => $outside->public_id]))->assertNotFound();
        $this->actingAs($owner)->get(route('owner.stores.show', ['store' => (string) $primary->id]))->assertNotFound();
    }

    public function test_owner_can_create_store_with_normalized_codes_and_membership(): void
    {
        [$owner] = $this->ownerWithStore();

        Livewire::actingAs($owner)->test(Form::class)
            ->set('name', 'Store Baru')
            ->set('storeCode', 'nwb')
            ->set('branchName', 'Branch Utama')
            ->set('branchCode', 'bpputm')
            ->call('save')
            ->assertRedirect();

        $store = Store::query()->where('store_code', 'NWB')->firstOrFail();
        $this->assertSame('BPPUTM', $store->branch_code);
        $this->assertTrue($owner->workspaceStores()->whereKey($store->id)->exists());
        $this->assertSame(StoreStatus::Active, $store->status);
    }

    public function test_invalid_codes_are_rejected(): void
    {
        [$owner] = $this->ownerWithStore();

        Livewire::actingAs($owner)->test(Form::class)
            ->set('name', 'Invalid Store')
            ->set('storeCode', 'NO!')
            ->set('branchCode', 'TOO-LONG')
            ->call('save')
            ->assertHasErrors(['storeCode', 'branchCode']);
    }

    public function test_owner_can_deactivate_and_reactivate_store_and_inactive_store_leaves_operational_context(): void
    {
        [$owner, $primary, $branch] = $this->ownerWithStore(true);
        app(ActiveStoreContext::class)->switch($owner, $branch->public_id);

        Livewire::actingAs($owner)->test(Index::class)
            ->call('askToDeactivate', $branch->public_id)
            ->call('deactivate');

        $this->assertSame(StoreStatus::Inactive, $branch->refresh()->status);
        $this->assertFalse(app(ActiveStoreContext::class)->availableStores($owner->fresh())->contains('id', $branch->id));
        $this->assertSame($primary->public_id, app(ActiveStoreContext::class)->identifier($owner->fresh()));

        Livewire::actingAs($owner)->test(Index::class)->call('reactivate', $branch->public_id);
        $this->assertSame(StoreStatus::Active, $branch->refresh()->status);
    }

    public function test_store_codes_cannot_change_after_transaction_history(): void
    {
        [$owner, $primary] = $this->ownerWithStore();
        InvoiceSequence::query()->create([
            'store_id' => $primary->id,
            'business_date' => '2026-10-05',
            'scope_key' => $primary->id.'|-|2026-10-05',
            'last_sequence' => 1,
        ]);

        $this->expectException(ValidationException::class);
        app(\App\Actions\Owner\UpdateStoreAction::class)->execute($owner, $primary, [
            'name' => $primary->name,
            'store_code' => 'NEW',
            'branch_code' => $primary->branch_code,
        ]);
    }

    /** @return array{User, Store, Store} */
    private function ownerWithStore(bool $withBranch = false): array
    {
        $primary = Store::query()->create(['name' => 'Owner Primary', 'store_code' => 'OWN']);
        $outside = Store::query()->create(['name' => 'Outside Store', 'store_code' => 'OUT']);
        $owner = User::factory()->create(['role' => UserRole::Owner, 'is_active' => true, 'store_id' => $primary->id]);
        $owner->workspaceStores()->sync([$primary->id => ['is_default' => true]]);

        if (! $withBranch) {
            return [$owner, $primary, $outside];
        }

        $branch = Store::query()->create(['name' => 'Owner Branch', 'store_code' => 'BRN']);
        $owner->workspaceStores()->attach($branch->id, ['is_default' => false]);
        return [$owner, $primary, $branch];
    }
}
