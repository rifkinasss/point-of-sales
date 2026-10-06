<?php

namespace App\Actions\Manager;

use App\Models\User;

class UpdateManagerProfileAction
{
    /** @param array{name: string, email: string} $data */
    public function handle(User $manager, array $data): User
    {
        $manager->forceFill([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
        ])->save();

        return $manager->refresh();
    }
}
