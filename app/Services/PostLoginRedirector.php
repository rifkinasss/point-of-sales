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
            UserRole::Admin => route('filament.admin.pages.dashboard'),
            UserRole::Owner => route('owner.dashboard'),
            UserRole::Manager => route('manager.dashboard'),
            UserRole::Cashier => $this->cashierPath($user),
        };
    }

    private function cashierPath(User $user): string
    {
        return CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists()
            ? route('cashier.pos')
            : route('cashier.open-shift');
    }
}
