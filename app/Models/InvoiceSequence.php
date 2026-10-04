<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceSequence extends Model
{
    protected $fillable = [
        'store_id',
        'branch_code',
        'business_date',
        'scope_key',
        'last_sequence',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'last_sequence' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
