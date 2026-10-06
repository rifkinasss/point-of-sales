<?php

namespace App\Queries;

use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManagerProductQuery
{
    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        $query = $this->base($manager, $filters);
        $query->with(['category', 'inventory'])
            ->addSelect(['last_movement_at' => DB::table('stock_movements')->select('occurred_at')->whereColumn('product_id', 'products.id')->latest('occurred_at')->limit(1)]);

        $sort = $filters['sort'] ?? 'name';
        if ($sort === 'stock_asc' || $sort === 'stock_desc') {
            $direction = $sort === 'stock_asc' ? 'asc' : 'desc';
            $query->orderByRaw("COALESCE((SELECT quantity FROM inventories WHERE inventories.product_id = products.id), 0) {$direction}");
        } else {
            $query->orderBy('name');
        }

        return $query->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $manager, string $publicId): ?Product
    {
        return Product::query()->withoutGlobalScopes()
            ->with(['category', 'unit', 'inventory', 'optionGroups.values'])
            ->whereIn('store_id', $this->storeIds($manager))
            ->where('public_id', $publicId)
            ->first();
    }

    public function categories(User $manager): array
    {
        return Category::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager))->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'name'])->all();
    }

    /** @return array{active:int,low:int,out:int,onHand:int,movementsToday:int} */
    public function metrics(User $manager): array
    {
        $base = Product::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($manager))->where('is_active', true);
        $tracked = (clone $base)->where('product_type', ProductType::Stockable);

        return [
            'active' => (clone $base)->count(),
            'low' => (clone $tracked)->whereHas('inventory', fn ($inventory) => $inventory->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'products.minimum_stock'))->count(),
            'out' => (clone $tracked)->where(function (Builder $query): void { $query->whereDoesntHave('inventory')->orWhereHas('inventory', fn ($inventory) => $inventory->where('quantity', '<=', 0)); })->count(),
            'onHand' => (int) ((clone $tracked)->leftJoin('inventories', 'inventories.product_id', '=', 'products.id')->sum('inventories.quantity')),
            'movementsToday' => DB::table('stock_movements')->whereIn('store_id', $this->storeIds($manager))->whereDate('occurred_at', today($manager->store?->timezone ?: config('app.timezone')))->count(),
        ];
    }

    private function base(User $manager, array $filters): Builder
    {
        $query = Product::query()->withoutGlobalScopes()->whereIn('products.store_id', $this->storeIds($manager));
        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->where(fn (Builder $q) => $q->whereRaw('LOWER(products.name) LIKE ?', [$needle])->orWhereRaw('LOWER(products.sku) LIKE ?', [$needle])->orWhereRaw('LOWER(COALESCE(products.barcode, \'\')) LIKE ?', [$needle]));
        }
        if (filled($filters['category'] ?? null)) {
            $query->whereHas('category', fn ($category) => $category->withoutGlobalScopes()->where('public_id', $filters['category'])->whereIn('store_id', $this->storeIds($manager)));
        }
        if (($filters['active'] ?? '') !== '') {
            $query->where('is_active', (bool) ((string) $filters['active'] === '1'));
        }
        if (($filters['stockStatus'] ?? '') === 'low') {
            $query->where('product_type', ProductType::Stockable)->whereHas('inventory', fn ($inventory) => $inventory->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'products.minimum_stock'));
        } elseif (($filters['stockStatus'] ?? '') === 'out') {
            $query->where('product_type', ProductType::Stockable)->where(fn (Builder $q) => $q->whereDoesntHave('inventory')->orWhereHas('inventory', fn ($inventory) => $inventory->where('quantity', '<=', 0)));
        } elseif (($filters['stockStatus'] ?? '') === 'safe') {
            $query->where(function (Builder $q): void { $q->where('product_type', ProductType::NonStockable)->orWhereHas('inventory', fn ($inventory) => $inventory->whereColumn('quantity', '>', 'products.minimum_stock')); });
        }

        return $query;
    }

    /** @return array<int, int> */
    private function storeIds(User $manager): array
    {
        return app(ActiveStoreContext::class)->scope($manager)->ids();
    }
}
