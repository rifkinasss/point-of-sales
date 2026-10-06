<?php

namespace App\Queries;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Services\ActiveStoreContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class OwnerPromotionQuery
{
    public function paginate(User $owner, array $filters): LengthAwarePaginator
    {
        $now = CarbonImmutable::now(config('app.timezone', 'UTC')); $query = Promotion::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->with(['store', 'product']);
        if (filled($filters['store'] ?? null)) { $store = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $filters['store']); $query->where('store_id', $store?->id ?: 0); }
        if (filled($filters['search'] ?? null)) { $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%'; $query->whereRaw('LOWER(name) LIKE ?', [$needle]); }
        if (filled($filters['type'] ?? null)) $query->where('type', $filters['type']);
        match ($filters['status'] ?? '') { 'active' => $query->where('is_active', true)->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now)), 'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now), 'expired' => $query->whereNotNull('ends_at')->where('ends_at', '<', $now), 'inactive' => $query->where('is_active', false), default => null };
        return $query->orderBy('name')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $owner, string $publicId): ?Promotion { return Promotion::query()->withoutGlobalScopes()->with(['store', 'product'])->whereIn('store_id', $this->storeIds($owner))->where('public_id', $publicId)->first(); }
    public function products(User $owner, ?string $storePublicId = null): array { $query = Product::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->where('is_active', true)->orderBy('name'); if ($storePublicId) { $store = app(ActiveStoreContext::class)->authorizedStores($owner)->firstWhere('public_id', $storePublicId); $query->where('store_id', $store?->id ?: 0); } return $query->get(['id', 'public_id', 'store_id', 'name', 'sku'])->all(); }
    public function status(Promotion $promotion): array { $now = CarbonImmutable::now(config('app.timezone', 'UTC')); if (! $promotion->is_active) return ['key' => 'inactive', 'label' => 'Nonaktif', 'class' => 'bg-background text-muted']; if ($promotion->ends_at && $promotion->ends_at->lt($now)) return ['key' => 'expired', 'label' => 'Berakhir', 'class' => 'bg-slate-100 text-slate-700']; if ($promotion->starts_at && $promotion->starts_at->gt($now)) return ['key' => 'scheduled', 'label' => 'Belum dimulai', 'class' => 'bg-primary-soft text-primary']; return ['key' => 'active', 'label' => 'Aktif', 'class' => 'bg-emerald-50 text-emerald-700']; }
    public function metrics(User $owner): array { $promos = Promotion::query()->withoutGlobalScopes()->whereIn('store_id', $this->storeIds($owner))->get(['is_active', 'starts_at', 'ends_at']); return collect(['active' => 0, 'scheduled' => 0, 'expired' => 0, 'inactive' => 0])->merge($promos->countBy(fn (Promotion $promotion): string => $this->status($promotion)['key']))->all(); }
    /** @return array<int, int> */ public function storeIds(User $owner): array { return app(ActiveStoreContext::class)->authorizedStores($owner)->pluck('id')->all(); }
}
