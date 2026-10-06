<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToCurrentStore, HasPublicId;

    protected $fillable = ['store_id', 'public_id', 'name', 'symbol', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
