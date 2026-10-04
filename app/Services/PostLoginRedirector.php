<?php

namespace App\Services;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;

class PostLoginRedirector
{
    public function pathFor(User $user): string
    {
        return match ($user->role) {
            UserRole::Admin => '/admin',
            UserRole::Owner => '/owner',
            UserRole::Manager => '/manager',
            UserRole::Cashier => $this->cashierPath($user),
        };
    }

    private function cashierPath(User $user): string
    {
        return CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists()
            ? '/cashier/pos'
            : '/cashier/open-shift';
    }
}
