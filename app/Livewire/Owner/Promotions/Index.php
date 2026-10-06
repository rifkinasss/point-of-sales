<?php

namespace App\Livewire\Owner\Promotions;

use App\Actions\Owner\CreatePromotionAction;
use App\Enums\UserRole;
use App\Models\User;
use App\Queries\OwnerPromotionQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public string $search = ''; public string $store = ''; public string $status = ''; public string $type = ''; public bool $createOpen = false; public string $storePublicId = ''; public string $promotionName = ''; public string $promotionType = 'percentage'; public string $promotionValue = ''; public string $startsAt = ''; public string $endsAt = ''; public string $productPublicId = ''; public string $memberTier = ''; public bool $isActive = true;
    public function mount(): void { $owner = $this->authorizeOwner(); $this->storePublicId = (string) app(ActiveStoreContext::class)->current($owner)?->public_id; if (request()->routeIs('owner.promotions.create')) $this->createOpen = true; }
    public function updatedSearch(): void { $this->resetPage(); } public function updatedStore(): void { $this->resetPage(); } public function updatedStatus(): void { $this->resetPage(); } public function updatedType(): void { $this->resetPage(); }
    public function openCreate(): void { $this->resetValidation(); $this->reset(['promotionName', 'promotionValue', 'startsAt', 'endsAt', 'productPublicId', 'memberTier']); $this->promotionType = 'percentage'; $this->isActive = true; $this->storePublicId = (string) app(ActiveStoreContext::class)->current($this->authorizeOwner())?->public_id; $this->createOpen = true; }
    public function closeCreate(): void { $this->createOpen = false; $this->resetValidation(); }
    public function saveCreate(): void { $owner = $this->authorizeOwner(); $data = $this->validate($this->rules()); $store = app(ActiveStoreContext::class)->availableStores($owner)->firstWhere('public_id', $data['storePublicId']); abort_unless($store, 404); $product = filled($data['productPublicId']) ? app(OwnerPromotionQuery::class)->products($owner, $store->public_id) : []; $productId = collect($product)->firstWhere('public_id', $data['productPublicId'])['id'] ?? null; if (filled($data['productPublicId']) && ! $productId) abort(404); $promotion = app(CreatePromotionAction::class)->execute($owner, $store, ['product_id' => $productId, 'name' => trim($data['promotionName']), 'type' => $data['promotionType'], 'value' => (int) $data['promotionValue'], 'member_tier' => filled($data['memberTier']) ? $data['memberTier'] : null, 'starts_at' => $data['startsAt'] ?: null, 'ends_at' => $data['endsAt'] ?: null, 'is_active' => (bool) $data['isActive']]); $this->closeCreate(); session()->flash('status', 'Promo berhasil dibuat.'); $this->redirectRoute('owner.promotions.show', ['promotion' => $promotion->public_id]); }
    public function render() { $owner = $this->authorizeOwner(); $query = app(OwnerPromotionQuery::class); return view('livewire.owner.promotions.index', ['promotions' => $query->paginate($owner, ['search' => $this->search, 'store' => $this->store, 'status' => $this->status, 'type' => $this->type]), 'stores' => app(ActiveStoreContext::class)->availableStores($owner), 'products' => $query->products($owner, $this->storePublicId), 'metrics' => $query->metrics($owner)])->layout('components.layouts.owner', ['title' => 'Promo', 'activeNav' => 'promotions']); }
    private function rules(): array { return ['storePublicId' => ['required', 'string'], 'promotionName' => ['required', 'string', 'max:255'], 'promotionType' => ['required', Rule::in(['percentage', 'fixed', 'fixed_price'])], 'promotionValue' => ['required', 'integer', 'min:0'], 'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'productPublicId' => ['nullable', 'string'], 'memberTier' => ['nullable', Rule::in(['', 'regular', 'silver', 'gold', 'platinum'])], 'isActive' => ['boolean']]; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
