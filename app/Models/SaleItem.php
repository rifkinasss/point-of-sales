<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'sku',
        'unit_name',
        'quantity',
        'unit_price',
        'modifier_total',
        'modifiers',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'line_total' => 'integer',
            'modifier_total' => 'integer',
            'modifiers' => 'array',
            'quantity' => 'integer',
            'unit_price' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
