<?php

namespace App\Livewire\Manager\Customers;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerCustomerQuery;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $active = '1';
    public string $activity = '';

    public function mount(): void { $this->authorizeManager(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedActive(): void { $this->resetPage(); }
    public function updatedActivity(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'activity']);
        $this->active = '1';
        $this->resetPage();
    }

    public function render()
    {
        $manager = $this->authorizeManager();
        $query = app(ManagerCustomerQuery::class);

        return view('livewire.manager.customers.index', [
            'customers' => $query->paginate($manager, ['search' => $this->search, 'active' => $this->active, 'activity' => $this->activity, 'perPage' => 20]),
            'metrics' => $query->metrics($manager),
        ])->layout('components.layouts.manager', ['title' => 'Pelanggan', 'activeNav' => 'customers']);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);
        return $user;
    }
}
