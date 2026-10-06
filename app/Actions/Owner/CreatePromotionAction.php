<?php

namespace App\Actions\Owner;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePromotionAction
{
    public function execute(User $owner, Store $store, array $attributes): Promotion
    {
        $this->authorizeStore($owner, $store); $this->validateProduct($store, $attributes['product_id'] ?? null);
        $this->validateValue($attributes['type'], (int) $attributes['value']);
        return DB::transaction(fn (): Promotion => Promotion::query()->withoutGlobalScopes()->create($attributes + ['store_id' => $store->id]));
    }

    public function validateValue(string $type, int $value): void
    {
        if ($value < 0 || ($type === 'percentage' && $value > 100)) throw ValidationException::withMessages(['promotionValue' => $type === 'percentage' ? 'Persentase promo maksimal 100%.' : 'Nilai promo tidak valid.']);
    }

    public function validateProduct(Store $store, ?int $productId): void { if ($productId !== null && ! Product::query()->withoutGlobalScopes()->whereKey($productId)->where('store_id', $store->id)->where('is_active', true)->exists()) throw ValidationException::withMessages(['productPublicId' => 'Produk promo tidak valid untuk store ini.']); }
    protected function authorizeStore(User $owner, Store $store): void { abort_unless($owner->role->value === 'owner' && $owner->is_active, 403); abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $store->id) && $store->isActive(), 404); }
}
