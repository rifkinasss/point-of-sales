<?php

namespace App\Livewire\Manager;

use App\Actions\Manager\ChangeManagerPasswordAction;
use App\Actions\Manager\UpdateManagerProfileAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActiveStoreContext;
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
    public ?string $successMessage = null;

    public function mount(?string $section = 'profile'): void
    {
        $manager = $this->authorizeManager();
        abort_unless(in_array($section, ['profile', 'security', 'access'], true), 404);
        $this->section = $section;
        $this->name = (string) $manager->name;
        $this->email = (string) $manager->email;
    }

    public function saveProfile(UpdateManagerProfileAction $action): void
    {
        $manager = $this->authorizeManager();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($manager->getKey())],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        $action->handle($manager, $data);
        $this->successMessage = 'Profil berhasil diperbarui.';
    }

    public function changePassword(ChangeManagerPasswordAction $action): void
    {
        $manager = $this->authorizeManager();
        $data = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed:newPasswordConfirmation'],
        ], [
            'currentPassword.required' => 'Password saat ini wajib diisi.',
            'newPassword.required' => 'Password baru wajib diisi.',
            'newPassword.min' => 'Password baru minimal 8 karakter.',
            'newPassword.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        try {
            $action->handle($manager, $data['currentPassword'], $data['newPassword']);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }
        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->successMessage = 'Password berhasil diperbarui.';
    }

    public function render()
    {
        $manager = $this->authorizeManager();

        return view('livewire.manager.settings', [
            'manager' => $manager,
            'stores' => app(ActiveStoreContext::class)->availableStores($manager),
        ])->layout('components.layouts.manager', [
            'title' => 'Pengaturan Akun',
            'activeNav' => 'settings',
        ]);
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active, 403);

        return $user;
    }
}
