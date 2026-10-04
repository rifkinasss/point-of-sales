<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleWorkspaceRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_render_livewire_workspace(): void
    {
        $manager = $this->user(UserRole::Manager);

        $this->actingAs($manager)->get('/manager')->assertOk()->assertSee('Manager Dashboard');
    }

    public function test_owner_can_render_livewire_workspace(): void
    {
        $owner = $this->user(UserRole::Owner);

        $this->actingAs($owner)->get('/owner')->assertOk()->assertSee('Owner Overview');
    }

    public function test_operational_roles_cannot_cross_into_other_role_workspaces(): void
    {
        $cashier = $this->user(UserRole::Cashier);
        $manager = $this->user(UserRole::Manager);
        $owner = $this->user(UserRole::Owner);

        $this->actingAs($cashier)->get('/manager')->assertForbidden();
        $this->actingAs($cashier)->get('/owner')->assertForbidden();
        $this->actingAs($manager)->get('/owner')->assertForbidden();
        $this->actingAs($owner)->get('/manager')->assertForbidden();
    }

    private function user(UserRole $role): User
    {
        $store = Store::query()->create([
            'name' => 'Store '.$role->value,
            'slug' => 'store-'.$role->value.'-'.fake()->unique()->numberBetween(1, 999999),
        ]);

        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
            'store_id' => $store->id,
        ]);
    }
}
