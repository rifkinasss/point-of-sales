<?php

namespace App\Livewire\Owner\Stores;

use App\Actions\Owner\SetStoreStatusAction;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStoreQuery;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';
    public string $status = '';
    public ?string $confirmingPublicId = null;

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function askToDeactivate(string $publicId): void
    {
        $this->store($publicId);
        $this->confirmingPublicId = $publicId;
    }

    public function deactivate(): void
    {
        $store = $this->store($this->confirmingPublicId);
        app(SetStoreStatusAction::class)->execute($this->authorizeOwner(), $store, StoreStatus::Inactive);
        $this->confirmingPublicId = null;
        session()->flash('status', 'Store berhasil dinonaktifkan.');
    }

    public function reactivate(string $publicId): void
    {
        app(SetStoreStatusAction::class)->execute($this->authorizeOwner(), $this->store($publicId), StoreStatus::Active);
        session()->flash('status', 'Store berhasil diaktifkan kembali.');
    }

    public function render()
    {
        $owner = $this->authorizeOwner();
        $query = app(OwnerStoreQuery::class);

        return view('livewire.owner.stores.index', [
            'stores' => $query->filtered($owner, $this->search, $this->status),
        ])->layout('components.layouts.owner', ['title' => 'Toko & Cabang', 'activeNav' => 'stores']);
    }

    private function store(?string $publicId): Store
    {
        abort_if(blank($publicId), 404);
        return app(OwnerStoreQuery::class)->findAuthorized($this->authorizeOwner(), $publicId);
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
