<?php

namespace App\Models;

use App\Enums\SaleStatus;
use App\Models\Concerns\BelongsToCurrentStore;
use Furic\FilamentRedeemCodes\Models\RedeemCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Sale extends Model
{
    use BelongsToCurrentStore;
    use Concerns\HasPublicId;

    protected $fillable = [
        'store_id',
        'public_id',
        'invoice_number',
        'cashier_shift_id',
        'cashier_id',
        'customer_id',
        'redeem_code_id',
        'promotion_code',
        'promotion_discount_total',
        'status',
        'subtotal',
        'item_discount_total',
        'transaction_discount_total',
        'grand_total',
        'note',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'grand_total' => 'integer',
            'item_discount_total' => 'integer',
            'promotion_discount_total' => 'integer',
            'status' => SaleStatus::class,
            'subtotal' => 'integer',
            'transaction_discount_total' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $sale): void {
            if ($sale->isDirty('invoice_number')) {
                throw new \LogicException('Invoice number is immutable after sale creation.');
            }
        });
    }

    public function cashierShift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function redeemCode(): BelongsTo
    {
        return $this->belongsTo(RedeemCode::class);
    }

    public function canTransitionTo(SaleStatus $target): bool
    {
        return $this->status->canTransitionTo($target);
    }

    public function transitionTo(SaleStatus $target): self
    {
        if (! $this->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'sale' => "Transisi status {$this->status->value} ke {$target->value} tidak diizinkan.",
            ]);
        }

        $this->forceFill(['status' => $target]);

        return $this;
    }
}
