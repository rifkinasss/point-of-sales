<?php

namespace App\Livewire\Owner\Customers;

use App\Actions\Owner\UpdateCustomerAction;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Queries\OwnerCustomerQuery;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Show extends Component
{
    public string $customerPublicId = ''; public bool $editOpen = false; public string $code = ''; public string $cardNumber = ''; public string $tier = 'regular'; public string $name = ''; public string $phone = ''; public string $email = ''; public bool $isActive = true;
    public function mount(Customer|string $customer): void { $this->authorizeOwner(); $this->customerPublicId = $customer instanceof Customer ? $customer->public_id : $customer; abort_unless($this->customer, 404); }
    public function openEdit(): void { $customer = $this->customer; $this->code = $customer->code; $this->cardNumber = (string) ($customer->card_number ?? ''); $this->tier = (string) ($customer->tier ?? 'regular'); $this->name = $customer->name; $this->phone = (string) ($customer->phone ?? ''); $this->email = (string) ($customer->email ?? ''); $this->isActive = $customer->is_active; $this->resetValidation(); $this->editOpen = true; }
    public function saveEdit(): void { $owner = $this->authorizeOwner(); $customer = app(OwnerCustomerQuery::class)->find($owner, $this->customerPublicId); abort_unless($customer, 404); $data = $this->validate(['code' => ['required', 'string', 'max:30'], 'cardNumber' => ['nullable', 'string', 'max:80'], 'tier' => ['required', Rule::in(['regular', 'silver', 'gold', 'platinum'])], 'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'isActive' => ['boolean']]); app(UpdateCustomerAction::class)->execute($owner, $customer, ['code' => strtoupper(trim($data['code'])), 'card_number' => filled($data['cardNumber']) ? trim($data['cardNumber']) : null, 'tier' => $data['tier'], 'name' => trim($data['name']), 'phone' => filled($data['phone']) ? trim($data['phone']) : null, 'email' => filled($data['email']) ? mb_strtolower(trim($data['email'])) : null, 'is_active' => $data['isActive']]); $this->editOpen = false; session()->flash('status', 'Profil pelanggan berhasil diperbarui.'); }
    public function render() { $owner = $this->authorizeOwner(); $customer = $this->customer; abort_unless($customer, 404); return view('livewire.owner.customers.show', ['customer' => $customer, 'metrics' => app(OwnerCustomerQuery::class)->customerMetrics($owner, $customer), 'transactions' => app(OwnerCustomerQuery::class)->transactions($owner, $customer)])->layout('components.layouts.owner', ['title' => 'Detail Pelanggan', 'activeNav' => 'customers']); }
    public function getCustomerProperty(): ?Customer { return $this->customerPublicId ? app(OwnerCustomerQuery::class)->find($this->authorizeOwner(), $this->customerPublicId) : null; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
