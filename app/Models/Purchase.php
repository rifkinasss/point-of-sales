<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToCurrentStore;
    protected $fillable = ['store_id', 'supplier_id', 'purchase_number', 'status', 'payment_status', 'subtotal', 'discount_total', 'grand_total', 'paid_total', 'received_at', 'due_at', 'received_by', 'note'];
    protected function casts(): array { return ['subtotal' => 'integer', 'discount_total' => 'integer', 'grand_total' => 'integer', 'paid_total' => 'integer', 'received_at' => 'datetime', 'due_at' => 'datetime']; }
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
    public function payments(): HasMany { return $this->hasMany(PurchasePayment::class); }
}
