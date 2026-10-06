<?php

namespace App\Filament\Widgets\Archive;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = -2;

    protected static ?string $heading = 'Stok rendah';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query()
                ->with('inventory')
                ->leftJoin('inventories', 'inventories.product_id', '=', 'products.id')
                ->where('products.is_active', true)
                ->where('products.product_type', ProductType::Stockable)
                ->whereRaw('COALESCE(inventories.quantity, 0) <= products.minimum_stock')
                ->select('products.*'))
            ->columns([
                TextColumn::make('name')->label('Produk')->searchable(),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('inventory.quantity')->label('Stok')->default(0),
                TextColumn::make('minimum_stock')->label('Minimum'),
            ])
            ->defaultSort('name')
            ->paginated([5, 10]);
    }
}
