<?php

namespace App\Livewire\Owner;

use App\Actions\Owner\ChangeOwnerPasswordAction;
use App\Actions\Owner\UpdateOwnerProfileAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActiveStoreContext;
use App\Services\BrowserTimezone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Settings extends Component
{
    public string $section = 'profile';
    public string $name = '';
    public string $email = '';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public string $sessionPassword = '';
    public ?string $successMessage = null;

    public function mount(?string $section = 'profile'): void
    {
        $owner = $this->authorizeOwner();
        abort_unless(in_array($section, ['profile', 'security', 'regional'], true), 404);
        $this->section = $section;
        $this->name = (string) $owner->name;
        $this->email = (string) $owner->email;
    }

    public function saveProfile(UpdateOwnerProfileAction $action): void
    {
        $owner = $this->authorizeOwner();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($owner->getKey())],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        $action->execute($owner, $data);
        $this->successMessage = 'Profil berhasil diperbarui.';
    }

    public function changePassword(ChangeOwnerPasswordAction $action): void
    {
        $owner = $this->authorizeOwner();
        $data = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed:newPasswordConfirmation'],
        ], [
            'currentPassword.required' => 'Kata sandi saat ini wajib diisi.',
            'newPassword.required' => 'Kata sandi baru wajib diisi.',
            'newPassword.min' => 'Kata sandi baru minimal 8 karakter.',
            'newPassword.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        try {
            $action->execute($owner, $data['currentPassword'], $data['newPassword']);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->invalidateOtherSessions($owner);
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->successMessage = 'Password berhasil diubah.';
    }

    public function logoutOtherSessions(): void
    {
        $owner = $this->authorizeOwner();
        $this->validate(['sessionPassword' => ['required', 'string']], ['sessionPassword.required' => 'Kata sandi saat ini wajib diisi.']);

        if (! Hash::check($this->sessionPassword, (string) $owner->password)) {
            $this->addError('sessionPassword', 'Kata sandi saat ini tidak sesuai.');
            return;
        }

        $this->invalidateOtherSessions($owner);
        $this->reset('sessionPassword');
        $this->successMessage = 'Sesi pada perangkat lain telah dikeluarkan.';
    }

    public function render()
    {
        $owner = $this->authorizeOwner();
        $sessionRows = $this->sessionRows($owner);

        return view('livewire.owner.settings', [
            'owner' => $owner,
            'stores' => app(ActiveStoreContext::class)->authorizedStores($owner),
            'timezone' => app(BrowserTimezone::class)->current(),
            'sessions' => $sessionRows,
            'sessionManagementAvailable' => config('session.driver') === 'database',
        ])->layout('components.layouts.owner', ['title' => 'Pengaturan Akun', 'activeNav' => 'settings']);
    }

    /** @return array<int,array<string,mixed>> */
    private function sessionRows(User $owner): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $currentId = request()->session()->getId();

        return DB::table('sessions')->where('user_id', $owner->getKey())->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity'])->map(fn ($session): array => [
            'current' => $session->id === $currentId,
            'ip' => $session->ip_address ?: 'IP tidak tersedia',
            'agent' => str($session->user_agent ?: 'Browser tidak diketahui')->limit(90)->toString(),
            'last_activity' => $session->last_activity,
        ])->all();
    }

    private function invalidateOtherSessions(User $owner): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table('sessions')->where('user_id', $owner->getKey())->where('id', '<>', request()->session()->getId())->delete();
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);

        return $user;
    }
}
