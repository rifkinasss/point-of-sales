<?php

namespace App\Actions\Owner;

use App\Models\Promotion;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class UpdatePromotionAction extends CreatePromotionAction
{
    public function execute(User $owner, Promotion $promotion, array $attributes): Promotion
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403); abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $promotion->store_id), 404);
        $store = Store::query()->findOrFail($promotion->store_id); $this->validateProduct($store, $attributes['product_id'] ?? null); $this->validateValue($attributes['type'], (int) $attributes['value']);
        return DB::transaction(function () use ($promotion, $attributes): Promotion { $promotion->update($attributes); return $promotion->refresh(); });
    }
}
