<?php

namespace App\Livewire\Owner\Customers;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerCustomerQuery;
use App\Services\ActiveStoreContext;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = ''; public string $store = ''; public string $active = '1';
    public function mount(): void { $this->authorizeOwner(); }
    public function updatedSearch(): void { $this->resetPage(); } public function updatedStore(): void { $this->resetPage(); } public function updatedActive(): void { $this->resetPage(); }
    public function resetFilters(): void { $this->reset(['search', 'store']); $this->active = '1'; $this->resetPage(); }
    public function render() { $owner = $this->authorizeOwner(); $query = app(OwnerCustomerQuery::class); return view('livewire.owner.customers.index', ['customers' => $query->paginate($owner, ['search' => $this->search, 'store' => $this->store, 'active' => $this->active]), 'stores' => app(ActiveStoreContext::class)->availableStores($owner), 'metrics' => $query->metrics($owner)])->layout('components.layouts.owner', ['title' => 'Pelanggan', 'activeNav' => 'customers']); }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
