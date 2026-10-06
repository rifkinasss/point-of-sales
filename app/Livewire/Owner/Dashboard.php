<?php

namespace App\Livewire\Owner;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerDashboardQuery;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
    }

    public function render()
    {
        $user = request()->user();

        return view('livewire.owner.dashboard', [
            'dashboard' => app(OwnerDashboardQuery::class)->dashboard($user),
        ])->layout('components.layouts.owner', ['title' => 'Dashboard Owner', 'activeNav' => 'dashboard']);
    }
}
