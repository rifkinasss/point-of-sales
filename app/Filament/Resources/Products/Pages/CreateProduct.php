<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function beforeCreate(): void
    {
        $user = Filament::auth()->user();

        if ($user instanceof User && $user->store && ! $user->store->canAddProduct()) {
            throw ValidationException::withMessages([
                'data.name' => "Paket Free dibatasi {$user->store->product_limit} produk aktif. Upgrade paket untuk menambah produk.",
            ]);
        }
    }
}
