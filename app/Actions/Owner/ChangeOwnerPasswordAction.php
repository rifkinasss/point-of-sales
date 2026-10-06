<?php

namespace App\Actions\Owner;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangeOwnerPasswordAction
{
    public function execute(User $owner, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, (string) $owner->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $owner->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => null,
        ])->save();
    }
}
