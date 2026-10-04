<?php

namespace App\Livewire\Manager\Products;

use App\Enums\UserRole;
use App\Models\User;
use App\Queries\ManagerProductQuery;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $category = '';
    public string $stockStatus = '';
    public string $active = '1';
    public string $sort = 'name';

    public function mount(): void
    {
        $this->authorizeManager();
        $this->stockStatus = (string) request()->query('stock_status', '');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedCategory(): void { $this->resetPage(); }
    public function updatedStockStatus(): void { $this->resetPage(); }
    public function updatedActive(): void { $this->resetPage(); }
    public function updatedSort(): void { $this->resetPage(); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'stockStatus', 'sort']);
        $this->active = '1';
        $this->resetPage();
    }

    public function render()
    {
        $manager = $this->authorizeManager();
        $query = app(ManagerProductQuery::class);

        return view('livewire.manager.products.index', [
            'products' => $query->paginate($manager, ['search' => $this->search, 'category' => $this->category, 'stockStatus' => $this->stockStatus, 'active' => $this->active, 'sort' => $this->sort, 'perPage' => 20]),
            'categories' => $query->categories($manager),
            'metrics' => $query->metrics($manager),
        ])->layout('components.layouts.manager', ['title' => 'Produk & Stok', 'activeNav' => 'products']);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);

        return $user;
    }
}
