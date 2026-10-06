<?php

namespace App\Livewire\Owner\Staff;

use App\Actions\Owner\SetStaffStatusAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerStaffQuery;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $role = '';
    public string $store = '';
    public string $status = '';
    public ?string $confirmingPublicId = null;

    public function mount(): void
    {
        $this->authorizeOwner();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedRole(): void { $this->resetPage(); }
    public function updatedStore(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function askToDeactivate(string $publicId): void
    {
        app(OwnerStaffQuery::class)->findAuthorized($this->authorizeOwner(), $publicId);
        $this->confirmingPublicId = $publicId;
    }

    public function deactivate(): void
    {
        $owner = $this->authorizeOwner();
        $user = app(OwnerStaffQuery::class)->findAuthorized($owner, (string) $this->confirmingPublicId);
        try {
            app(SetStaffStatusAction::class)->execute($owner, $user, false);
            $this->confirmingPublicId = null;
            session()->flash('status', 'Akses pengguna berhasil dinonaktifkan.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->confirmingPublicId = null;
            $this->addError('user', $exception->validator->errors()->first('user'));
        }
    }

    public function reactivate(string $publicId): void
    {
        $owner = $this->authorizeOwner();
        $user = app(OwnerStaffQuery::class)->findAuthorized($owner, $publicId);
        app(SetStaffStatusAction::class)->execute($owner, $user, true);
        session()->flash('status', 'Akses pengguna berhasil diaktifkan kembali.');
    }

    public function render()
    {
        $owner = $this->authorizeOwner();
        $query = app(OwnerStaffQuery::class);
        return view('livewire.owner.staff.index', [
            'users' => $query->users($owner, ['search' => $this->search, 'role' => $this->role, 'store' => $this->store, 'status' => $this->status])->paginate(20),
            'stores' => $query->authorizedStores($owner),
        ])->layout('components.layouts.owner', ['title' => 'Manager & Staff', 'activeNav' => 'staff']);
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
