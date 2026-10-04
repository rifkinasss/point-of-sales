<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipeIngredient extends Model
{
    protected $fillable = ['ingredient_product_id', 'quantity_required'];

    protected function casts(): array
    {
        return ['quantity_required' => 'integer'];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }
}
