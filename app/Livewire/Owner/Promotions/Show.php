<?php

namespace App\Livewire\Owner\Promotions;

use App\Actions\Owner\SetPromotionStatusAction;
use App\Enums\UserRole;
use App\Models\Promotion;
use App\Models\User;
use App\Queries\OwnerPromotionQuery;
use Livewire\Component;

class Show extends Component
{
    public string $promotionPublicId = '';
    public function mount(Promotion|string $promotion): void { $this->authorizeOwner(); $this->promotionPublicId = $promotion instanceof Promotion ? $promotion->public_id : $promotion; abort_unless($this->promotion, 404); }
    public function toggleActive(): void { $owner = $this->authorizeOwner(); $promotion = app(OwnerPromotionQuery::class)->find($owner, $this->promotionPublicId); abort_unless($promotion, 404); app(SetPromotionStatusAction::class)->execute($owner, $promotion, ! $promotion->is_active); }
    public function render() { $owner = $this->authorizeOwner(); $promotion = $this->promotion; abort_unless($promotion, 404); return view('livewire.owner.promotions.show', ['promotion' => $promotion, 'status' => app(OwnerPromotionQuery::class)->status($promotion)])->layout('components.layouts.owner', ['title' => 'Detail Promo', 'activeNav' => 'promotions']); }
    public function getPromotionProperty(): ?Promotion { return app(OwnerPromotionQuery::class)->find($this->authorizeOwner(), $this->promotionPublicId); }
    private function authorizeOwner(): User { $user = request()->user(); abort_unless($user instanceof User && $user->role === UserRole::Owner && $user->is_active, 403); return $user; }
}
