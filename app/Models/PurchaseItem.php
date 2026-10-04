<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'product_name', 'quantity', 'unit_cost', 'line_total'];
    protected function casts(): array { return ['quantity' => 'integer', 'unit_cost' => 'integer', 'line_total' => 'integer']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
