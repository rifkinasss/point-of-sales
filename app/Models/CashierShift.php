<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Models\Concerns\BelongsToCurrentStore;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierShift extends Model
{
    use BelongsToCurrentStore, HasPublicId;

    protected $fillable = [
        'store_id',
        'public_id',
        'user_id',
        'status',
        'opened_at',
        'opening_cash',
        'opening_note',
        'closed_at',
        'closing_cash_actual',
        'expected_cash',
        'cash_difference',
        'closing_note',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'cash_difference' => 'integer',
            'closed_at' => 'datetime',
            'closing_cash_actual' => 'integer',
            'expected_cash' => 'integer',
            'opened_at' => 'datetime',
            'opening_cash' => 'integer',
            'status' => ShiftStatus::class,
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class, 'shift_id');
    }
}
