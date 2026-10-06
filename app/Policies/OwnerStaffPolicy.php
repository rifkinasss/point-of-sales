<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStaffQuery;

class OwnerStaffPolicy
{
    public function manage(User $owner): bool
    {
        return $owner->role === UserRole::Owner && $owner->is_active;
    }

    public function view(User $owner, User $subject): bool
    {
        return $this->manage($owner)
            && in_array($subject->role, [UserRole::Manager, UserRole::Cashier], true)
            && app(OwnerStaffQuery::class)->isAuthorizedSubject($owner, $subject);
    }

    public function assign(User $owner, Store $store): bool
    {
        return $this->manage($owner)
            && $store->isActive()
            && app(OwnerStaffQuery::class)->isAuthorizedStore($owner, $store);
    }
}
