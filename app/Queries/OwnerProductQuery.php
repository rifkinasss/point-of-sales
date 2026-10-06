<?php

namespace App\Queries;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OwnerProductQuery
{
    public function paginate(User $owner, array $filters): LengthAwarePaginator
    {
        $query = Product::query()->withoutGlobalScopes()
            ->whereIn('products.store_id', $this->storeIds($owner))
            ->with(['store', 'category', 'unit', 'inventory']);

        if (filled($filters['store'] ?? null)) {
            $storeId = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $filters['store'])?->id;
            $storeId ? $query->where('store_id', $storeId) : $query->whereRaw('1 = 0');
        }
        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->where(fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', [$needle])->orWhereRaw('LOWER(sku) LIKE ?', [$needle])->orWhereRaw("LOWER(COALESCE(barcode, '')) LIKE ?", [$needle]));
        }
        if (filled($filters['category'] ?? null)) {
            $query->whereHas('category', fn (Builder $q) => $q->withoutGlobalScopes()->where('public_id', $filters['category'])->whereIn('store_id', $this->storeIds($owner)));
        }
        if (($filters['active'] ?? '') !== '') {
            $query->where('is_active', (bool) ((string) $filters['active'] === '1'));
        }
        if (($filters['stockStatus'] ?? '') === 'low') {
            $query->where('product_type', ProductType::Stockable)->whereHas('inventory', fn (Builder $q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'products.minimum_stock'));
        } elseif (($filters['stockStatus'] ?? '') === 'out') {
            $query->where('product_type', ProductType::Stockable)->where(fn (Builder $q) => $q->whereDoesntHave('inventory')->orWhereHas('inventory', fn (Builder $inventory) => $inventory->where('quantity', '<=', 0)));
        }

        return $query->orderBy('name')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $owner, string $publicId): ?Product
    {
        return Product::query()->withoutGlobalScopes()->with(['store', 'category', 'unit', 'inventory', 'optionGroups.values'])
            ->whereIn('store_id', $this->storeIds($owner))->where('public_id', $publicId)->first();
    }

    public function categories(User $owner, ?string $storePublicId = null, bool $includeInactive = false): Collection
    {
        $query = Category::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->orderBy('name');
        if ($storePublicId) {
            $storeId = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $storePublicId)?->id;
            $query->where('store_id', $storeId ?: 0);
        }
        if (! $includeInactive) $query->where('is_active', true);
        return $query->get(['id', 'public_id', 'store_id', 'name', 'description', 'is_active']);
    }

    public function units(User $owner, ?string $storePublicId = null): Collection
    {
        $query = Unit::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->where('is_active', true)->orderBy('name');
        if ($storePublicId) {
            $storeId = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $storePublicId)?->id;
            $query->where('store_id', $storeId ?: 0);
        }
        return $query->get(['id', 'public_id', 'name', 'symbol']);
    }

    public function metrics(User $owner): array
    {
        $base = Product::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->where('is_active', true);
        $tracked = (clone $base)->where('product_type', ProductType::Stockable);
        return [
            'active' => (clone $base)->count(),
            'low' => (clone $tracked)->whereHas('inventory', fn (Builder $q) => $q->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'products.minimum_stock'))->count(),
            'out' => (clone $tracked)->where(fn (Builder $q) => $q->whereDoesntHave('inventory')->orWhereHas('inventory', fn (Builder $inventory) => $inventory->where('quantity', '<=', 0)))->count(),
        ];
    }

    /** @return array<int, int> */
    public function storeIds(User $owner): array
    {
        return app(ActiveStoreContext::class)->authorizedStores($owner)->pluck('id')->all();
    }
}
