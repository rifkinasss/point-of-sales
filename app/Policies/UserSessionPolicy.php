<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Smony\FilamentUserSessions\Models\Session;

class UserSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, Session $session): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Session $session): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }
}
