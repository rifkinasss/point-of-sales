<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreInvoice extends Model
{
    use BelongsToCurrentStore;
    protected $fillable = ['store_id', 'store_subscription_id', 'invoice_number', 'status', 'amount', 'due_at', 'paid_at', 'payment_reference', 'note'];
    protected function casts(): array { return ['amount' => 'integer', 'due_at' => 'datetime', 'paid_at' => 'datetime']; }
    public function subscription(): BelongsTo { return $this->belongsTo(StoreSubscription::class, 'store_subscription_id'); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
}
