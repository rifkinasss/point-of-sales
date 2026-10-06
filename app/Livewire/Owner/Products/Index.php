<?php

namespace App\Livewire\Owner\Products;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerProductQuery;
use App\Services\ActiveStoreContext;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public string $store = '';
    public string $stockStatus = '';
    public string $active = '1';

    public function mount(): void { $this->authorizeOwner(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedStore(): void { $this->resetPage(); }
    public function updatedStockStatus(): void { $this->resetPage(); }
    public function updatedActive(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'store', 'stockStatus']);
        $this->active = '1'; $this->resetPage();
    }

    public function render()
    {
        $owner = $this->authorizeOwner(); $query = app(OwnerProductQuery::class);
        return view('livewire.owner.products.index', [
            'products' => $query->paginate($owner, ['search' => $this->search, 'category' => $this->category, 'store' => $this->store, 'stockStatus' => $this->stockStatus, 'active' => $this->active]),
            'stores' => app(ActiveStoreContext::class)->availableStores($owner),
            'categories' => $query->categories($owner, $this->store), 'metrics' => $query->metrics($owner),
        ])->layout('components.layouts.owner', ['title' => 'Produk & Stok', 'activeNav' => 'products']);
    }

    private function authorizeOwner(): User
    {
        $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user;
    }
}
