<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreSubscription extends Model
{
    use BelongsToCurrentStore;

    protected $fillable = ['store_id', 'plan', 'status', 'amount', 'requested_at', 'starts_at', 'ends_at', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'requested_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StoreInvoice::class);
    }
}
