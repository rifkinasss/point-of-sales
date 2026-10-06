<?php

namespace App\Actions\Owner;

use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Support\Facades\DB;

class SaveCategoryAction
{
    public function execute(User $owner, Store $store, ?Category $category, array $attributes): Category
    {
        abort_unless($owner->role->value === 'owner' && $owner->is_active, 403);
        abort_unless(app(ActiveStoreContext::class)->authorizedStores($owner)->contains('id', $store->id) && $store->isActive(), 404);
        abort_unless(! $category || $category->store_id === $store->id, 404);
        return DB::transaction(function () use ($store, $category, $attributes): Category {
            $category ??= new Category(['store_id' => $store->id]);
            $category->fill(['name' => trim($attributes['name']), 'description' => filled($attributes['description'] ?? null) ? trim($attributes['description']) : null]);
            $category->save();
            return $category;
        });
    }
}
