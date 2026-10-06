<?php

namespace App\Livewire\Owner\Customers;

use App\Actions\Owner\CreateCustomerAction;
use App\Actions\Owner\UpdateCustomerAction;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Queries\OwnerCustomerQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?string $customerPublicId = null; public string $storePublicId = ''; public string $code = ''; public string $cardNumber = ''; public string $tier = 'regular'; public string $name = ''; public string $phone = ''; public string $email = ''; public bool $isActive = true;
    public function mount(Customer|string|null $customer = null): void { $owner = $this->authorizeOwner(); if (! $customer) { $this->storePublicId = (string) app(ActiveStoreContext::class)->current($owner)?->public_id; return; } $item = app(OwnerCustomerQuery::class)->find($owner, $customer instanceof Customer ? $customer->public_id : $customer); abort_unless($item, 404); $this->customerPublicId = $item->public_id; $this->storePublicId = (string) $item->store?->public_id; $this->code = $item->code; $this->cardNumber = (string) ($item->card_number ?? ''); $this->tier = (string) ($item->tier ?? 'regular'); $this->name = $item->name; $this->phone = (string) ($item->phone ?? ''); $this->email = (string) ($item->email ?? ''); $this->isActive = $item->is_active; }
    public function save(): void { $owner = $this->authorizeOwner(); $data = $this->validate($this->rules()); $stores = app(ActiveStoreContext::class)->availableStores($owner); $store = $stores->firstWhere('public_id', $this->storePublicId); abort_unless($store, 404); $attributes = ['code' => strtoupper(trim($data['code'])), 'card_number' => filled($data['cardNumber']) ? trim($data['cardNumber']) : null, 'tier' => $data['tier'], 'name' => trim($data['name']), 'phone' => filled($data['phone']) ? trim($data['phone']) : null, 'email' => filled($data['email']) ? mb_strtolower(trim($data['email'])) : null, 'is_active' => (bool) $data['isActive']]; if ($this->customerPublicId) { $customer = app(OwnerCustomerQuery::class)->find($owner, $this->customerPublicId); abort_unless($customer, 404); $customer = app(UpdateCustomerAction::class)->execute($owner, $customer, $attributes); } else { $customer = app(CreateCustomerAction::class)->execute($owner, $store, $attributes); } session()->flash('status', 'Pelanggan berhasil disimpan.'); $this->redirectRoute('owner.customers.show', ['customer' => $customer->public_id]); }
    public function render() { $owner = $this->authorizeOwner(); return view('livewire.owner.customers.form', ['editing' => (bool) $this->customerPublicId, 'stores' => app(ActiveStoreContext::class)->availableStores($owner)])->layout('components.layouts.owner', ['title' => $this->customerPublicId ? 'Edit Pelanggan' : 'Tambah Pelanggan', 'activeNav' => 'customers']); }
    private function rules(): array { $code = Rule::unique('customers', 'code')->where(fn ($q) => $q->where('store_id', app(ActiveStoreContext::class)->authorizedStores($this->authorizeOwner())->firstWhere('public_id', $this->storePublicId)?->id)); $card = Rule::unique('customers', 'card_number')->where(fn ($q) => $q->where('store_id', app(ActiveStoreContext::class)->authorizedStores($this->authorizeOwner())->firstWhere('public_id', $this->storePublicId)?->id)); if ($this->customerPublicId) { $code->ignore($this->customerPublicId, 'public_id'); $card->ignore($this->customerPublicId, 'public_id'); } return ['storePublicId' => ['required', 'string'], 'code' => ['required', 'string', 'max:30', $code], 'cardNumber' => ['nullable', 'string', 'max:80', $card], 'tier' => ['required', Rule::in(['regular', 'silver', 'gold', 'platinum'])], 'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'], 'isActive' => ['boolean']]; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
