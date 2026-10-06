<?php

namespace App\Livewire\Owner\Staff;

use App\Actions\Owner\SendStaffPasswordResetAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerStaffQuery;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

class Show extends Component
{
    public string $userPublicId;

    public function mount(User $user): void
    {
        $this->userPublicId = app(OwnerStaffQuery::class)->findAuthorized($this->authorizeOwner(), (string) $user->public_id)->public_id;
    }

    public function sendPasswordReset(): void
    {
        $status = app(SendStaffPasswordResetAction::class)->execute($this->authorizeOwner(), $this->staff());
        session()->flash('status', $status === Password::RESET_LINK_SENT ? 'Link reset password berhasil dikirim.' : 'Link reset password tidak dapat dikirim saat ini.');
    }

    public function render()
    {
        $user = $this->staff();
        return view('livewire.owner.staff.show', [
            'staff' => $user,
            'stores' => $user->role === UserRole::Manager ? $user->workspaceStores()->orderBy('name')->get() : collect([$user->store])->filter(),
            'activeShift' => $user->cashierShifts()->where('status', 'open')->with('store')->latest('opened_at')->first(),
        ])->layout('components.layouts.owner', ['title' => $user->name, 'activeNav' => 'staff']);
    }

    private function staff(): User
    {
        return app(OwnerStaffQuery::class)->findAuthorized($this->authorizeOwner(), $this->userPublicId);
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
