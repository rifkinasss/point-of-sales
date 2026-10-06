<?php

namespace App\Livewire\Manager\Customers;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Queries\ManagerCustomerQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Show extends Component
{
    public ?string $customerPublicId = null;
    public bool $editModalOpen = false;
    public string $name = '';
    public string $phone = '';
    public string $email = '';

    public function mount(string|Customer $customer): void
    {
        $this->authorizeManager();
        $this->customerPublicId = $customer instanceof Customer ? $customer->public_id : $customer;
        abort_unless($this->customer instanceof Customer, 404);
    }

    public function openEdit(): void
    {
        $customer = $this->customer;
        abort_unless($customer instanceof Customer, 404);
        $this->resetValidation();
        $this->name = $customer->name;
        $this->phone = (string) ($customer->phone ?? '');
        $this->email = (string) ($customer->email ?? '');
        $this->editModalOpen = true;
    }

    public function closeEdit(): void
    {
        $this->editModalOpen = false;
        $this->reset(['name', 'phone', 'email']);
        $this->resetValidation();
    }

    public function saveEdit(): void
    {
        $manager = $this->authorizeManager();
        app(ActiveStoreContext::class)->requireSingleStore($manager);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'name.required' => 'Nama pelanggan wajib diisi.',
            'email.email' => 'Email pelanggan tidak valid.',
        ]);
        $customer = app(ManagerCustomerQuery::class)->find($manager, (string) $this->customerPublicId);
        abort_unless($customer instanceof Customer, 404);
        $customer->update(['name' => trim($data['name']), 'phone' => filled($data['phone']) ? trim($data['phone']) : null, 'email' => filled($data['email']) ? trim($data['email']) : null]);
        $this->closeEdit();
        session()->flash('status', 'Profil pelanggan berhasil diperbarui.');
    }

    public function render()
    {
        $manager = $this->authorizeManager();
        $customer = $this->customer;
        abort_unless($customer instanceof Customer, 404);
        return view('livewire.manager.customers.show', [
            'customer' => $customer,
            'metrics' => app(ManagerCustomerQuery::class)->customerMetrics($manager, $customer),
            'transactions' => app(ManagerCustomerQuery::class)->transactions($manager, $customer),
        ])->layout('components.layouts.manager', ['title' => 'Detail Pelanggan', 'activeNav' => 'customers']);
    }

    public function getCustomerProperty(): ?Customer
    {
        $manager = $this->authorizeManager();
        return $this->customerPublicId ? app(ManagerCustomerQuery::class)->find($manager, $this->customerPublicId) : null;
    }

    private function authorizeManager(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403);
        return $user;
    }
}
