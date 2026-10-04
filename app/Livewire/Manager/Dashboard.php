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
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active, 403);
    }

    public function render()
    {
        $user = request()->user();
        $query = app(ManagerDashboardQuery::class);

        return view('livewire.manager.dashboard', [
            'summary' => $query->summary($user),
            'recentSales' => $query->recentSales($user),
        ])->layout('components.layouts.workspace', ['title' => 'Manager Dashboard', 'role' => 'Manager', 'nav' => [['label' => 'Dashboard', 'href' => route('manager.dashboard')]]]);
    }
}
