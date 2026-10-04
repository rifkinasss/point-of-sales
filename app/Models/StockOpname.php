<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockOpname extends Model
{
    use BelongsToCurrentStore;
    protected $fillable = ['store_id', 'number', 'status', 'counted_by', 'counted_at', 'note'];
    protected function casts(): array { return ['counted_at' => 'datetime']; }
    public function items(): HasMany { return $this->hasMany(StockOpnameItem::class); }
}
