<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerStoreContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_switch_only_to_an_authorized_store_using_public_id(): void
    {
        [$manager, $primary, $branch, $outside] = $this->managerWithStores();

        $this->actingAs($manager)
            ->post(route('manager.store-context.switch'), ['store' => $branch->public_id])
            ->assertRedirect();

        $this->assertSame($branch->public_id, app(ActiveStoreContext::class)->identifier($manager));

        $this->actingAs($manager)
            ->post(route('manager.store-context.switch'), ['store' => $outside->public_id])
            ->assertForbidden();

        $this->actingAs($manager)
            ->post(route('manager.store-context.switch'), ['store' => (string) $branch->id])
            ->assertForbidden();
    }

    public function test_manager_can_select_all_branches_for_read_only_context(): void
    {
        [$manager, $primary, $branch] = $this->managerWithStores();

        $this->actingAs($manager)
            ->post(route('manager.store-context.switch'), ['store' => 'all'])
            ->assertRedirect();

        $scope = app(ActiveStoreContext::class)->scope($manager);
        $this->assertTrue($scope->all);
        $this->assertEqualsCanonicalizing([$primary->id, $branch->id], $scope->ids());
    }

    public function test_manager_store_selector_does_not_expose_numeric_store_id(): void
    {
        [$manager, $primary] = $this->managerWithStores();

        $response = $this->actingAs($manager)->get(route('manager.dashboard'));

        $response->assertOk()->assertSee($primary->public_id)->assertDontSee('value="'.$primary->id.'"');
    }

    /** @return array{User, Store, Store, Store} */
    private function managerWithStores(): array
    {
        $primary = Store::query()->create(['name' => 'Primary', 'slug' => 'primary-'.fake()->unique()->numberBetween(1, 999999)]);
        $branch = Store::query()->create(['name' => 'Branch', 'slug' => 'branch-'.fake()->unique()->numberBetween(1, 999999)]);
        $outside = Store::query()->create(['name' => 'Outside', 'slug' => 'outside-'.fake()->unique()->numberBetween(1, 999999)]);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'is_active' => true, 'store_id' => $primary->id]);
        $manager->managedStores()->sync([
            $primary->id => ['is_default' => true],
            $branch->id => ['is_default' => false],
        ]);

        return [$manager, $primary, $branch, $outside];
    }
}
