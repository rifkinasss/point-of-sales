<?php

namespace App\Livewire\Owner\Categories;

use App\Actions\Owner\SaveCategoryAction;
use App\Actions\Owner\SetCategoryStatusAction;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Queries\OwnerProductQuery;
use App\Services\ActiveStoreContext;
use Livewire\Component;

class Index extends Component
{
    public string $storePublicId = ''; public string $categoryPublicId = ''; public string $name = ''; public string $description = ''; public bool $modalOpen = false;
    public function mount(): void { $owner = $this->authorizeOwner(); $this->storePublicId = (string) app(ActiveStoreContext::class)->current($owner)?->public_id; }
    public function create(): void { $this->reset(['categoryPublicId', 'name', 'description']); $this->modalOpen = true; $this->resetValidation(); }
    public function edit(string $publicId): void { $owner = $this->authorizeOwner(); $category = app(OwnerProductQuery::class)->categories($owner, null, true)->firstWhere('public_id', $publicId); abort_unless($category, 404); $this->categoryPublicId = $category->public_id; $this->storePublicId = (string) app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('id', $category->store_id)?->public_id; $this->name = $category->name; $this->description = (string) ($category->description ?? ''); $this->modalOpen = true; }
    public function save(): void { $owner = $this->authorizeOwner(); $data = $this->validate(['storePublicId' => ['required', 'string'], 'name' => ['required', 'string', 'max:100'], 'description' => ['nullable', 'string', 'max:1000']]); $store = app(ActiveStoreContext::class)->availableStores($owner)->firstWhere('public_id', $data['storePublicId']); abort_unless($store, 404); $category = $this->categoryPublicId ? app(OwnerProductQuery::class)->categories($owner, null, true)->firstWhere('public_id', $this->categoryPublicId) : null; app(SaveCategoryAction::class)->execute($owner, $store, $category, $data); $this->modalOpen = false; session()->flash('status', 'Kategori berhasil disimpan.'); }
    public function toggle(string $publicId): void { $owner = $this->authorizeOwner(); $category = app(OwnerProductQuery::class)->categories($owner, null, true)->firstWhere('public_id', $publicId); abort_unless($category, 404); app(SetCategoryStatusAction::class)->execute($owner, $category, ! $category->is_active); }
    public function render() { $owner = $this->authorizeOwner(); return view('livewire.owner.categories.index', ['categories' => app(OwnerProductQuery::class)->categories($owner, $this->storePublicId, true), 'stores' => app(ActiveStoreContext::class)->availableStores($owner)])->layout('components.layouts.owner', ['title' => 'Kategori Produk', 'activeNav' => 'products']); }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
