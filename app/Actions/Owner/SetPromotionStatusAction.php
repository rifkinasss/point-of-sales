<?php

namespace App\Actions\Owner;

use App\Models\Promotion;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Validation\ValidationException;

class SetPromotionStatusAction
{
    public function execute(User $owner, Promotion $promotion, bool $active): Promotion
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403); abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $promotion->store_id), 404);
        if ($active && $promotion->ends_at?->isPast()) throw ValidationException::withMessages(['promotion' => 'Promo yang sudah berakhir tidak dapat diaktifkan kembali tanpa jadwal baru.']);
        $promotion->update(['is_active' => $active]); return $promotion->refresh();
    }
}
