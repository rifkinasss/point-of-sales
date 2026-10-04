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
            'summary' => app(OwnerDashboardQuery::class)->summary($user),
        ])->layout('components.layouts.workspace', ['title' => 'Owner Overview', 'role' => 'Owner', 'nav' => [['label' => 'Overview', 'href' => route('owner.dashboard')]]]);
    }
}
