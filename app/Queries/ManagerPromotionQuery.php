<?php

namespace App\Queries;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManagerPromotionQuery
{
    public function paginate(User $manager, array $filters): LengthAwarePaginator
    {
        $now = CarbonImmutable::now($manager->store?->timezone ?: config('app.timezone', 'UTC'));
        $query = $this->base($manager, $filters);
        if (($filters['status'] ?? '') === 'active') $query->where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
        if (($filters['status'] ?? '') === 'scheduled') $query->where('is_active', true)->where('starts_at', '>', $now);
        if (($filters['status'] ?? '') === 'expired') $query->whereNotNull('ends_at')->where('ends_at', '<', $now);
        if (($filters['status'] ?? '') === 'inactive') $query->where('is_active', false);

        return $query->with('product')->orderBy('name')->paginate((int) ($filters['perPage'] ?? 20));
    }

    public function find(User $manager, string $publicId): ?Promotion
    {
        return Promotion::query()->withoutGlobalScopes()->with('product')->where('store_id', $manager->store_id)->where('public_id', $publicId)->first();
    }

    public function products(User $manager): array
    {
        return Product::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'name', 'sku'])->all();
    }

    /** @return array{active:int,scheduled:int,expired:int,inactive:int} */
    public function metrics(User $manager): array
    {
        $promotions = Promotion::query()->withoutGlobalScopes()->where('store_id', $manager->store_id)->get(['is_active', 'starts_at', 'ends_at']);
        $now = CarbonImmutable::now($manager->store?->timezone ?: config('app.timezone', 'UTC'));

        return collect(['active' => 0, 'scheduled' => 0, 'expired' => 0, 'inactive' => 0])->merge($promotions->countBy(fn (Promotion $promotion): string => $this->status($promotion, $now)['key']))->all();
    }

    /** @return array{key:string,label:string,class:string} */
    public function status(Promotion $promotion, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        if (! $promotion->is_active) return ['key' => 'inactive', 'label' => 'Inactive', 'class' => 'bg-background text-muted'];
        if ($promotion->ends_at && $promotion->ends_at->lt($now)) return ['key' => 'expired', 'label' => 'Expired', 'class' => 'bg-slate-100 text-slate-700'];
        if ($promotion->starts_at && $promotion->starts_at->gt($now)) return ['key' => 'scheduled', 'label' => 'Scheduled', 'class' => 'bg-primary-soft text-primary'];
        return ['key' => 'active', 'label' => 'Active', 'class' => 'bg-emerald-50 text-emerald-700'];
    }

    private function base(User $manager, array $filters): Builder
    {
        $query = Promotion::query()->withoutGlobalScopes()->where('promotions.store_id', $manager->store_id);
        if (filled($filters['search'] ?? null)) {
            $needle = '%'.mb_strtolower(trim((string) $filters['search'])).'%';
            $query->whereRaw('LOWER(promotions.name) LIKE ?', [$needle]);
        }
        if (filled($filters['type'] ?? null)) $query->where('type', $filters['type']);
        if (($filters['applicability'] ?? '') === 'product') $query->whereNotNull('product_id');
        if (($filters['applicability'] ?? '') === 'all') $query->whereNull('product_id');

        return $query;
    }
}
