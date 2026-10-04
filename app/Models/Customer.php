<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCurrentStore;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToCurrentStore, HasPublicId;

    protected $fillable = ['store_id', 'code', 'tier', 'card_number', 'name', 'phone', 'email', 'birth_date', 'address', 'points', 'credit_limit', 'credit_balance', 'is_active'];

    protected function casts(): array
    {
        return [
            'credit_balance' => 'integer',
            'credit_limit' => 'integer',
            'is_active' => 'boolean',
            'points' => 'integer',
            'birth_date' => 'date',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
