<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Models\Concerns\BelongsToCurrentStore;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCurrentStore, HasPublicId, SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'unit_id',
        'name',
        'sku',
        'barcode',
        'image_path',
        'product_type',
        'cost_price',
        'selling_price',
        'minimum_stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'is_active' => 'boolean',
            'minimum_stock' => 'integer',
            'product_type' => ProductType::class,
            'selling_price' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(ProductRecipeIngredient::class);
    }
}
