<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends Model
{
    use HasPublicId;

    protected $fillable = ['product_option_group_id', 'name', 'price_adjustment', 'is_default', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price_adjustment' => 'integer', 'is_active' => 'boolean', 'is_default' => 'boolean', 'sort_order' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_id');
    }
}
