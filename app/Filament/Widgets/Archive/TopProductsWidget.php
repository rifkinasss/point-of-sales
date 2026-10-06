<?php

namespace App\Filament\Widgets\Archive;

use App\Enums\SaleStatus;
use App\Models\SaleItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TopProductsWidget extends TableWidget
{
    protected static ?int $sort = -1;

    protected static ?string $heading = 'Produk terlaris hari ini';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.status', SaleStatus::Paid)
                ->where('sales.completed_at', '>=', now()->startOfDay())
                ->groupBy('sale_items.product_name', 'sale_items.sku')
                ->select([
                    DB::raw('MIN(sale_items.id) as id'),
                    'sale_items.product_name',
                    'sale_items.sku',
                    DB::raw('SUM(sale_items.quantity) as quantity_sold'),
                    DB::raw('SUM(sale_items.line_total) as revenue'),
                ]))
            ->columns([
                TextColumn::make('product_name')->label('Produk'),
                TextColumn::make('sku')->label('SKU'),
                TextColumn::make('quantity_sold')->label('Terjual')->numeric(),
                TextColumn::make('revenue')->label('Omzet')->money('IDR', locale: 'id'),
            ])
            ->defaultSort('quantity_sold', 'desc')
            ->paginated([5, 10]);
    }
}
