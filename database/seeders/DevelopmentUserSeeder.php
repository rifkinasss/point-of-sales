<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $store = Store::query()->where('slug', 'naslabs-market')->firstOrFail();

        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@naslabs.my.id',
                'legacy_emails' => ['admin@pos.test', 'admin@naslabs.id'],
                'role' => UserRole::Admin,
                'store_id' => null,
            ],
            [
                'name' => 'Owner',
                'email' => 'owner@naslabs.my.id',
                'legacy_emails' => ['owner@pos.test', 'owner@naslabs.id'],
                'role' => UserRole::Owner,
                'store_id' => $store->id,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@naslabs.my.id',
                'legacy_emails' => ['manager@pos.test', 'manager@naslabs.id'],
                'role' => UserRole::Manager,
                'store_id' => $store->id,
            ],
            [
                'name' => 'Cashier',
                'email' => 'cashier@naslabs.my.id',
                'legacy_emails' => ['cashier@pos.test', 'kasir@naslabs.id'],
                'role' => UserRole::Cashier,
                'store_id' => $store->id,
            ],
        ];

        foreach ($users as $user) {
            $record = User::query()->where('email', $user['email'])->first()
                ?? User::query()->whereIn('email', $user['legacy_emails'])->first()
                ?? new User;

            $record->forceFill([
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'store_id' => $user['store_id'],
                'password' => Hash::make('password'),
                'is_active' => true,
            ])->save();

            $record->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
