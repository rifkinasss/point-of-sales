<?php

namespace App\Actions\Owner;

use App\Models\User;

class UpdateOwnerProfileAction
{
    /** @param array{name:string,email:string} $attributes */
    public function execute(User $owner, array $attributes): User
    {
        $owner->forceFill([
            'name' => trim($attributes['name']),
            'email' => strtolower(trim($attributes['email'])),
        ])->save();

        return $owner->refresh();
    }
}
