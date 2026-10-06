<?php

namespace App\Models;

use App\Enums\StoreStatus;
use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Store extends Model
{
    protected $fillable = [
        'public_id',
        'name',
        'slug',
        'store_code',
        'branch_code',
        'branch_name',
        'status',
        'plan',
        'product_limit',
        'staff_limit',
        'logo_path',
        'location',
        'address',
        'phone',
        'email',
        'currency',
        'timezone',
        'tax_percentage',
        'invoice_prefix',
        'receipt_footer',
        'receipt_show_cashier',
        'receipt_show_tax',
    ];

    protected function casts(): array
    {
        return [
            'receipt_show_cashier' => 'boolean',
            'receipt_show_tax' => 'boolean',
            'tax_percentage' => 'decimal:2',
            'location' => Coordinate::class.':latitude,longitude',
            'product_limit' => 'integer',
            'staff_limit' => 'integer',
            'status' => StoreStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $store): void {
            if (! filled($store->public_id)) {
                $store->public_id = (string) Str::ulid();
            }

            if (filled($store->store_code)) {
                return;
            }

            $source = strtoupper((string) ($store->invoice_prefix ?: $store->slug ?: $store->name ?: 'STO'));
            $code = preg_replace('/[^A-Z0-9]/', '', $source) ?: 'STO';
            $code = str_pad(substr($code, 0, 3), 3, 'X');
            $attempt = 0;

            while (static::query()->where('store_code', $code)->exists()) {
                $attempt++;
                $code = strtoupper(substr(base_convert((string) crc32($source.$attempt), 10, 36), -3));
                $code = str_pad($code, 3, '0', STR_PAD_LEFT);
            }

            $store->store_code = $code;
        });
    }

    public function setStoreCodeAttribute(?string $value): void
    {
        $this->attributes['store_code'] = filled($value) ? Str::upper(trim($value)) : null;
    }

    public function setBranchCodeAttribute(?string $value): void
    {
        $this->attributes['branch_code'] = filled($value) ? Str::upper(trim($value)) : null;
    }

    public function isActive(): bool
    {
        return $this->status === StoreStatus::Active;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_memberships')->withPivot('is_default')->withTimestamps();
    }

    public function canAddProduct(): bool
    {
        return $this->product_limit < 1 || $this->products()->where('is_active', true)->count() < $this->product_limit;
    }

    public function canAddStaff(): bool
    {
        return $this->staff_limit < 1 || $this->users()->whereIn('role', ['manager', 'cashier'])->count() < $this->staff_limit;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function cashierShifts(): HasMany
    {
        return $this->hasMany(CashierShift::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StoreSubscription::class);
    }

    public function latestSubscription(): HasOne
    {
        return $this->hasOne(StoreSubscription::class)->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StoreInvoice::class);
    }
}
