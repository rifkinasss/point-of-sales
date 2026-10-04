<?php

namespace App\Livewire\Manager;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerDashboardQuery;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);
    }

    public function render()
    {
        $user = request()->user();
        $query = app(ManagerDashboardQuery::class);

        return view('livewire.manager.dashboard', [
            'dashboard' => $query->dashboard($user),
        ])->layout('components.layouts.manager', ['title' => 'Dashboard Operasional']);
    }
}
