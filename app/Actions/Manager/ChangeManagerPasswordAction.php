<?php

namespace App\Actions\Manager;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangeManagerPasswordAction
{
    public function handle(User $manager, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, (string) $manager->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $manager->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => null,
        ])->save();
    }
}
