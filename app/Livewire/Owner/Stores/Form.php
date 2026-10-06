<?php

namespace App\Livewire\Owner\Stores;

use App\Actions\Owner\CreateStoreAction;
use App\Actions\Owner\UpdateStoreAction;
use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use App\Queries\OwnerStoreQuery;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?string $storePublicId = null;
    public string $name = '';
    public string $storeCode = '';
    public string $branchName = '';
    public string $branchCode = '';
    public string $address = '';
    public string $phone = '';
    public bool $hasHistory = false;

    public function mount(?Store $store = null): void
    {
        $this->authorizeOwner();
        if (! $store) {
            return;
        }

        $store = app(OwnerStoreQuery::class)->findAuthorized($this->authorizeOwner(), (string) $store->public_id);
        $this->storePublicId = $store->public_id;
        $this->name = (string) $store->name;
        $this->storeCode = (string) $store->store_code;
        $this->branchName = (string) ($store->branch_name ?? '');
        $this->branchCode = (string) ($store->branch_code ?? '');
        $this->address = (string) ($store->address ?? '');
        $this->phone = (string) ($store->phone ?? '');
        $this->hasHistory = $store->sales()->exists();
    }

    public function save(): void
    {
        $owner = $this->authorizeOwner();
        $this->storeCode = strtoupper(trim($this->storeCode));
        $this->branchCode = strtoupper(trim($this->branchCode));
        $this->validate($this->rules());

        $attributes = [
            'name' => trim($this->name),
            'store_code' => $this->storeCode,
            'branch_name' => filled($this->branchName) ? trim($this->branchName) : null,
            'branch_code' => filled($this->branchCode) ? $this->branchCode : null,
            'address' => filled($this->address) ? trim($this->address) : null,
            'phone' => filled($this->phone) ? trim($this->phone) : null,
        ];

        if ($this->storePublicId) {
            $store = app(OwnerStoreQuery::class)->findAuthorized($owner, $this->storePublicId);
            app(UpdateStoreAction::class)->execute($owner, $store, $attributes);
            session()->flash('status', 'Perubahan store berhasil disimpan.');
            $this->redirectRoute('owner.stores.show', ['store' => $store->public_id]);
            return;
        }

        $store = app(CreateStoreAction::class)->execute($owner, $attributes);
        session()->flash('status', 'Store berhasil dibuat.');
        $this->redirectRoute('owner.stores.show', ['store' => $store->public_id]);
    }

    public function render()
    {
        return view('livewire.owner.stores.form', [
            'editing' => filled($this->storePublicId),
        ])->layout('components.layouts.owner', [
            'title' => $this->storePublicId ? 'Edit Store' : 'Tambah Store',
            'activeNav' => 'stores',
        ]);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $unique = Rule::unique('stores', 'store_code');
        if ($this->storePublicId) {
            $unique->ignore($this->storePublicId, 'public_id');
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'storeCode' => ['required', 'string', 'size:3', 'regex:/^[A-Z0-9]{3}$/', $unique],
            'branchName' => ['nullable', 'string', 'max:150'],
            'branchCode' => ['nullable', 'string', 'max:7', 'regex:/^[A-Z0-9]{1,7}$/'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }

    private function authorizeOwner(): User
    {
        $user = request()->user();
        abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403);
        return $user;
    }
}
