<?php

namespace App\Actions\Owner;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class CreateProductAction
{
    public function execute(User $owner, Store $store, array $attributes, array $groups = []): Product
    {
        abort_unless($owner->role === UserRole::Owner && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $store->id) && $store->isActive(), 404);
        $this->validateReferences($store, $attributes);

        return DB::transaction(function () use ($store, $attributes, $groups): Product {
            $product = Product::create($attributes + ['store_id' => $store->id]);
            $this->syncGroups($product, $groups);
            return $product->fresh(['store', 'category', 'unit', 'inventory', 'optionGroups.values']);
        });
    }

    public function validateReferences(Store $store, array $attributes): void
    {
        if (filled($attributes['category_id'] ?? null)) abort_unless(Category::query()->withoutGlobalScopes()->whereKey($attributes['category_id'])->where('store_id', $store->id)->exists(), 422);
        abort_unless(Unit::query()->withoutGlobalScopes()->whereKey($attributes['unit_id'])->where('store_id', $store->id)->exists(), 422);
    }

    /** @param array<int, array<string, mixed>> $groups */
    public function syncGroups(Product $product, array $groups): void
    {
        foreach (array_values($groups) as $groupIndex => $groupData) {
            if (blank(trim((string) ($groupData['name'] ?? '')))) continue;
            $group = $product->optionGroups()->create([
                'name' => trim((string) $groupData['name']), 'is_required' => (bool) ($groupData['is_required'] ?? false),
                'minimum_selections' => (int) ($groupData['is_required'] ?? false ? 1 : 0), 'maximum_selections' => 1, 'sort_order' => $groupIndex,
            ]);
            foreach (array_values($groupData['values'] ?? []) as $valueIndex => $valueData) {
                if (blank(trim((string) ($valueData['name'] ?? '')))) continue;
                $group->values()->create(['name' => trim((string) $valueData['name']), 'price_adjustment' => (int) ($valueData['price_adjustment'] ?? 0), 'is_default' => (bool) ($valueData['is_default'] ?? false), 'is_active' => true, 'sort_order' => $valueIndex]);
            }
        }
    }
}
