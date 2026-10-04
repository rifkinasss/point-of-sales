<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionBatch extends Model
{
    use BelongsToCurrentStore;
    protected $fillable = ['store_id', 'number', 'product_id', 'quantity_produced', 'produced_by', 'produced_at', 'note'];
    protected function casts(): array { return ['quantity_produced' => 'integer', 'produced_at' => 'datetime']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function producedBy(): BelongsTo { return $this->belongsTo(User::class, 'produced_by'); }
}
