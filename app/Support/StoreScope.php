<?php

namespace App\Support;

use App\Models\Store;
use Illuminate\Support\Collection;

final readonly class StoreScope
{
    public function __construct(
        public Collection $stores,
        public bool $all = false,
    ) {}

    /** @return array<int, int> */
    public function ids(): array
    {
        return $this->stores->pluck('id')->map(fn ($id): int => (int) $id)->all();
    }

    public function isEmpty(): bool
    {
        return $this->stores->isEmpty();
    }

    public function selected(): ?Store
    {
        return $this->all ? null : $this->stores->first();
    }

    public function requiresSingleStore(): bool
    {
        return ! $this->all && $this->stores->count() === 1;
    }
}
