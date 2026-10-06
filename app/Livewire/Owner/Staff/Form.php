<?php

namespace App\Livewire\Owner\Staff;

use App\Actions\Owner\CreateStaffAction;
use App\Actions\Owner\UpdateStaffAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerStaffQuery;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Form extends Component
{
    public ?string $userPublicId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'manager';
    public string $status = 'active';
    /** @var array<int, string> */
    public array $selectedStorePublicIds = [];

    public function mount(?User $user = null): void
    {
        $owner = $this->authorizeOwner();
        if (! $user) {
            return;
        }
        $user = app(OwnerStaffQuery::class)->findAuthorized($owner, (string) $user->public_id);
        $this->userPublicId = $user->public_id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->status = $user->is_active ? 'active' : 'inactive';
        $this->selectedStorePublicIds = $user->role === UserRole::Manager
            ? $user->workspaceStores()->where('stores.status', 'active')->pluck('stores.public_id')->all()
            : ($user->store?->isActive() ? [$user->store->public_id] : []);
    }

    public function save(): void
    {
        $owner = $this->authorizeOwner();
        $this->validate($this->rules());
        try {
            if ($this->userPublicId) {
                $user = app(OwnerStaffQuery::class)->findAuthorized($owner, $this->userPublicId);
                app(UpdateStaffAction::class)->execute($owner, $user, $this->attributes(), $this->selectedStorePublicIds);
                session()->flash('status', 'Perubahan pengguna berhasil disimpan.');
            } else {
                $user = app(CreateStaffAction::class)->execute($owner, $this->attributes(), $this->selectedStorePublicIds);
                session()->flash('status', 'Pengguna berhasil dibuat. Kirim reset password dari halaman detail sebelum pengguna masuk.');
            }
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) $this->addError($field, $message);
            }
            return;
        }
        $this->redirectRoute('owner.staff.show', ['user' => $user->public_id]);
    }

    public function render()
    {
        return view('livewire.owner.staff.form', [
            'editing' => filled($this->userPublicId),
            'stores' => app(OwnerStaffQuery::class)->authorizedStores($this->authorizeOwner(), true),
        ])->layout('components.layouts.owner', ['title' => $this->userPublicId ? 'Edit Pengguna' : 'Tambah Pengguna', 'activeNav' => 'staff']);
    }

    /** @return array<string, mixed> */
    private function attributes(): array
    {
        return ['name' => $this->name, 'email' => $this->email, 'role' => $this->role, 'is_active' => $this->status === 'active'];
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $email = Rule::unique('users', 'email');
        if ($this->userPublicId) $email->ignore($this->userPublicId, 'public_id');
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $email],
            'role' => ['required', Rule::in([UserRole::Manager->value, UserRole::Cashier->value])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'selectedStorePublicIds' => ['array'],
            'selectedStorePublicIds.*' => ['string', 'distinct'],
        ];
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
