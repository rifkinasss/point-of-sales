<?php

namespace App\Actions\Owner;

use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\ProductOptionValue;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function execute(User $owner, Product $product, array $attributes, array $groups = []): Product
    {
        abort_unless($owner->is_active && $owner->role->value === 'owner', 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $product->store_id), 404);
        $store = Store::query()->findOrFail($product->store_id);
        (new CreateProductAction())->validateReferences($store, $attributes);

        return DB::transaction(function () use ($product, $attributes, $groups): Product {
            $product->update($attributes);
            $keepGroups = [];
            foreach (array_values($groups) as $groupIndex => $data) {
                if (blank(trim((string) ($data['name'] ?? '')))) continue;
                $group = ! empty($data['public_id']) ? $product->optionGroups()->where('public_id', $data['public_id'])->first() : null;
                $group ??= new ProductOptionGroup(['product_id' => $product->id]);
                $group->fill(['name' => trim((string) $data['name']), 'is_required' => (bool) ($data['is_required'] ?? false), 'minimum_selections' => (int) ($data['is_required'] ?? false ? 1 : 0), 'maximum_selections' => 1, 'sort_order' => $groupIndex]);
                $group->save();
                $keepGroups[] = $group->id;
                $keepValues = [];
                foreach (array_values($data['values'] ?? []) as $valueIndex => $valueData) {
                    if (blank(trim((string) ($valueData['name'] ?? '')))) continue;
                    $value = ! empty($valueData['public_id']) ? $group->values()->where('public_id', $valueData['public_id'])->first() : null;
                    $value ??= new ProductOptionValue(['product_option_group_id' => $group->id]);
                    $value->fill(['name' => trim((string) $valueData['name']), 'price_adjustment' => (int) ($valueData['price_adjustment'] ?? 0), 'is_default' => (bool) ($valueData['is_default'] ?? false), 'is_active' => true, 'sort_order' => $valueIndex]);
                    $value->save(); $keepValues[] = $value->id;
                }
                $group->values()->whereNotIn('id', $keepValues ?: [0])->delete();
            }
            $product->optionGroups()->whereNotIn('id', $keepGroups ?: [0])->delete();
            return $product->fresh(['store', 'category', 'unit', 'inventory', 'optionGroups.values']);
        });
    }
}
