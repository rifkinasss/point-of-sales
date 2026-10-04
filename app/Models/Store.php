<?php

namespace App\Models;

use EduardoRibeiroDev\FilamentLeaflet\ValueObjects\Coordinate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'name',
        'slug',
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
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StoreSubscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StoreInvoice::class);
    }
}
