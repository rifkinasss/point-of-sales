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
                'email' => 'admin@pos.test',
                'role' => UserRole::Admin,
                'store_id' => null,
            ],
            [
                'name' => 'Owner',
                'email' => 'owner@pos.test',
                'role' => UserRole::Owner,
                'store_id' => $store->id,
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@pos.test',
                'role' => UserRole::Manager,
                'store_id' => $store->id,
            ],
            [
                'name' => 'Cashier',
                'email' => 'cashier@pos.test',
                'role' => UserRole::Cashier,
                'store_id' => $store->id,
            ],
        ];

        foreach ($users as $user) {
            $record = User::updateOrCreate(
                [
                    'email' => $user['email'],
                ],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'store_id' => $user['store_id'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ],
            );

            $record->forceFill(['email_verified_at' => now()])->save();
        }
    }
}
