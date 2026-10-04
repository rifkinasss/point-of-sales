<?php

namespace App\Livewire\Manager\Promotions;

use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\ManagerPromotionQuery;
use Livewire\Component;

class Show extends Component
{
    public ?string $promotionPublicId = null;
    public bool $editModalOpen = false;
    public string $promotionName = '';
    public string $promotionType = 'percentage';
    public string $promotionValue = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $productPublicId = '';
    public string $memberTier = '';
    public bool $isActive = true;

    public function mount(string|Promotion $promotion): void { $this->authorizeManager(); $this->promotionPublicId = $promotion instanceof Promotion ? $promotion->public_id : $promotion; abort_unless($this->promotion instanceof Promotion, 404); }
    public function openEdit(): void { $promotion = $this->promotion; abort_unless($promotion, 404); $this->resetValidation(); $this->promotionName = $promotion->name; $this->promotionType = $promotion->type; $this->promotionValue = (string) $promotion->value; $this->startsAt = $promotion->starts_at?->format('Y-m-d\TH:i') ?? ''; $this->endsAt = $promotion->ends_at?->format('Y-m-d\TH:i') ?? ''; $this->productPublicId = $promotion->product?->public_id ?? ''; $this->memberTier = (string) ($promotion->member_tier ?? ''); $this->isActive = $promotion->is_active; $this->editModalOpen = true; }
    public function closeEdit(): void { $this->editModalOpen = false; $this->resetValidation(); }
    public function toggleActive(): void { $manager = $this->authorizeManager(); $promotion = app(ManagerPromotionQuery::class)->find($manager, (string) $this->promotionPublicId); abort_unless($promotion, 404); if (! $promotion->is_active && $promotion->ends_at?->isPast()) { $this->addError('promotion', 'Promo yang sudah berakhir tidak dapat diaktifkan kembali tanpa jadwal baru.'); return; } $promotion->update(['is_active' => ! $promotion->is_active]); }
    public function saveEdit(): void { $manager = $this->authorizeManager(); $data = $this->validate(['promotionName' => ['required', 'string', 'max:255'], 'promotionType' => ['required', 'in:percentage,fixed,fixed_price'], 'promotionValue' => ['required', 'integer', 'min:0'], 'startsAt' => ['nullable', 'date'], 'endsAt' => ['nullable', 'date', 'after:startsAt'], 'productPublicId' => ['nullable', 'string'], 'memberTier' => ['nullable', 'in:,regular,silver,gold,platinum'], 'isActive' => ['boolean']], ['endsAt.after' => 'Waktu berakhir harus setelah waktu mulai.']); if ($data['promotionType'] === 'percentage' && (int) $data['promotionValue'] > 100) { $this->addError('promotionValue', 'Persentase promo maksimal 100%.'); return; } $promotion = app(ManagerPromotionQuery::class)->find($manager, (string) $this->promotionPublicId); abort_unless($promotion, 404); $productId = filled($data['productPublicId']) ? app(\App\Queries\ManagerProductQuery::class)->find($manager, $data['productPublicId'])?->id : null; abort_if(filled($data['productPublicId']) && ! $productId, 404); $promotion->update(['product_id' => $productId, 'name' => trim($data['promotionName']), 'type' => $data['promotionType'], 'value' => (int) $data['promotionValue'], 'member_tier' => filled($data['memberTier']) ? $data['memberTier'] : null, 'starts_at' => $data['startsAt'] ?: null, 'ends_at' => $data['endsAt'] ?: null, 'is_active' => $data['isActive']]); $this->closeEdit(); session()->flash('status', 'Promo berhasil diperbarui.'); }
    public function render() { $manager = $this->authorizeManager(); $promotion = $this->promotion; abort_unless($promotion, 404); return view('livewire.manager.promotions.show', ['promotion' => $promotion, 'status' => app(ManagerPromotionQuery::class)->status($promotion), 'products' => app(ManagerPromotionQuery::class)->products($manager)])->layout('components.layouts.manager', ['title' => 'Detail Promo', 'activeNav' => 'promotions']); }
    public function getPromotionProperty(): ?Promotion { $manager = $this->authorizeManager(); return $this->promotionPublicId ? app(ManagerPromotionQuery::class)->find($manager, $this->promotionPublicId) : null; }
    private function authorizeManager(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Manager && $user->is_active && filled($user->store_id), 403); return $user; }
}
