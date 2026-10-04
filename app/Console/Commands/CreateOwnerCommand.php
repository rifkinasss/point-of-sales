<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('pos:create-owner
    {name? : Nama owner}
    {email? : Email owner}
    {--password= : Password akun owner (akan diminta bila kosong)}')]
#[Description('Membuat atau mempromosikan akun owner POS')]
class CreateOwnerCommand extends Command
{
    public function handle(): int
    {
        $name = $this->argument('name') ?? $this->ask('Nama owner');
        $email = mb_strtolower($this->argument('email') ?? $this->ask('Email owner'));

        if (blank($name) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Nama wajib diisi dan email harus valid.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $password = $this->option('password');

        if (! $user && blank($password)) {
            $password = $this->secret('Password owner');
        }

        if (! $user && blank($password)) {
            $this->error('Password wajib diisi untuk akun baru.');

            return self::FAILURE;
        }

        $attributes = [
            'name' => $name,
            'role' => UserRole::Owner,
            'is_active' => true,
        ];

        if (filled($password)) {
            $attributes['password'] = Hash::make($password);
        }

        if ($user) {
            $user->update($attributes);
        } else {
            $user = User::query()->create([
                ...$attributes,
                'email' => $email,
            ]);
        }

        $this->info("Akun owner {$user->email} siap digunakan pada /admin.");

        return self::SUCCESS;
    }
}
