<?php

namespace App\Livewire\Owner\Promotions;

use App\Actions\Owner\UpdatePromotionAction;
use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\OwnerPromotionQuery;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?string $promotionPublicId = null; public string $storePublicId = ''; public string $promotionName = ''; public string $promotionType = 'percentage'; public string $promotionValue = ''; public string $startsAt = ''; public string $endsAt = ''; public string $productPublicId = ''; public string $memberTier = ''; public bool $isActive = true;
    public function mount(Promotion|string $promotion): void { $owner = $this->authorizeOwner(); $item = app(OwnerPromotionQuery::class)->find($owner, $promotion instanceof Promotion ? $promotion->public_id : $promotion); abort_unless($item, 404); $this->promotionPublicId = $item->public_id; $this->storePublicId = (string) $item->store?->public_id; $this->promotionName = $item->name; $this->promotionType = $item->type; $this->promotionValue = (string) $item->value; $this->startsAt = $item->starts_at?->format('Y-m-d\TH:i') ?? ''; $this->endsAt = $item->ends_at?->format('Y-m-d\TH:i') ?? ''; $this->productPublicId = (string) ($item->product?->public_id ?? ''); $this->memberTier = (string) ($item->member_tier ?? ''); $this->isActive = $item->is_active; }
    public function save(): void { $owner = $this->authorizeOwner(); $data = $this->validate($this->rules()); $promotion = app(OwnerPromotionQuery::class)->find($owner, (string) $this->promotionPublicId); abort_unless($promotion, 404); $products = app(OwnerPromotionQuery::class)->products($owner, $this->storePublicId); $productId = filled($data['productPublicId']) ? collect($products)->firstWhere('public_id', $data['productPublicId'])['id'] ?? null : null; if (filled($data['productPublicId']) && ! $productId) abort(404); $promotion = app(UpdatePromotionAction::class)->execute($owner, $promotion, ['product_id' => $productId, 'name' => trim($data['promotionName']), 'type' => $data['promotionType'], 'value' => (int) $data['promotionValue'], 'member_tier' => filled($data['memberTier']) ? $data['memberTier'] : null, 'starts_at' => $data['startsAt'] ?: null, 'ends_at' => $data['endsAt'] ?: null, 'is_active' => (bool) $data['isActive']]); session()->flash('status', 'Promo berhasil disimpan.'); $this->redirectRoute('owner.promotions.show', ['promotion' => $promotion->public_id]); }
    public function render() { $owner = $this->authorizeOwner(); return view('livewire.owner.promotions.form', ['stores' => app(ActiveStoreContext::class)->availableStores($owner), 'products' => app(OwnerPromotionQuery::class)->products($owner, $this->storePublicId)])->layout('components.layouts.owner', ['title' => 'Edit Promo', 'activeNav' => 'promotions']); }
    private function rules(): array { return ['promotionName' => ['required', 'string', 'max:255'], 'promotionType' => ['required', Rule::in(['percentage', 'fixed', 'fixed_price'])], 'promotionValue' => ['required', 'integer', 'min:0'], 'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'productPublicId' => ['nullable', 'string'], 'memberTier' => ['nullable', Rule::in(['', 'regular', 'silver', 'gold', 'platinum'])], 'isActive' => ['boolean']]; }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
