<?php

namespace App\Livewire\Owner\Stores;

use App\Actions\Owner\SetStoreStatusAction;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStoreQuery;
use Livewire\Component;

class Show extends Component
{
    public Store $store;

    public function mount(Store $store): void
    {
        $owner = $this->authorizeOwner();
        $this->store = app(OwnerStoreQuery::class)->findAuthorized($owner, (string) $store->public_id);
    }

    public function deactivate(): void
    {
        app(SetStoreStatusAction::class)->execute($this->authorizeOwner(), $this->store, StoreStatus::Inactive);
        $this->store->refresh();
        session()->flash('status', 'Store berhasil dinonaktifkan.');
    }

    public function reactivate(): void
    {
        app(SetStoreStatusAction::class)->execute($this->authorizeOwner(), $this->store, StoreStatus::Active);
        $this->store->refresh();
        session()->flash('status', 'Store berhasil diaktifkan kembali.');
    }

    public function render()
    {
        return view('livewire.owner.stores.show', [
            'summary' => app(OwnerStoreQuery::class)->summary($this->store),
            'managers' => app(OwnerStoreQuery::class)->managers($this->store),
        ])->layout('components.layouts.owner', ['title' => $this->store->name, 'activeNav' => 'stores']);
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
