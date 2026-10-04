<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\DevelopmentUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DevelopmentUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_users_are_idempotent_and_assigned_to_the_canonical_store(): void
    {
        $store = Store::query()->create(['name' => 'NasLabs Market', 'slug' => 'naslabs-market']);

        app(DevelopmentUserSeeder::class)->run();
        app(DevelopmentUserSeeder::class)->run();

        $this->assertDatabaseCount('users', 4);

        $expected = [
            'admin@pos.test' => [UserRole::Admin, null],
            'owner@pos.test' => [UserRole::Owner, $store->id],
            'manager@pos.test' => [UserRole::Manager, $store->id],
            'cashier@pos.test' => [UserRole::Cashier, $store->id],
        ];

        foreach ($expected as $email => [$role, $storeId]) {
            $user = User::query()->where('email', $email)->firstOrFail();

            $this->assertSame($role, $user->role);
            $this->assertSame($storeId, $user->store_id);
            $this->assertTrue($user->is_active);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check('password', $user->password));
        }
    }
}
