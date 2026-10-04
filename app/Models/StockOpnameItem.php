<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnameItem extends Model
{
    protected $fillable = ['stock_opname_id', 'product_id', 'system_quantity', 'actual_quantity', 'difference'];
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
