<?php

namespace App\Filament\Resources\Products\Tables;

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\RecordInitialStockAction;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Satuan')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable(),
                TextColumn::make('product_type')
                    ->label('Tipe')
                    ->badge()
                    ->searchable(),
                TextColumn::make('cost_price')
                    ->label('Harga beli')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('selling_price')
                    ->label('Harga jual')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('minimum_stock')
                    ->label('Stok minimum')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->relationship('category', 'name')->label('Kategori'),
                SelectFilter::make('product_type')->options(ProductType::class)->label('Tipe produk'),
                TernaryFilter::make('is_active')->label('Status aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('initialStock')
                    ->label('Stok awal')
                    ->icon('heroicon-o-plus-circle')
                    ->visible(fn (Product $record): bool => $record->product_type->tracksInventory() && ! $record->stockMovements()->exists())
                    ->form([
                        TextInput::make('quantity')->label('Kuantitas')->numeric()->minValue(1)->required(),
                        Textarea::make('reason')->label('Catatan')->maxLength(1000),
                    ])
                    ->action(function (Product $record, array $data): void {
                        app(RecordInitialStockAction::class)->handle(
                            $record,
                            auth()->user(),
                            (int) $data['quantity'],
                            $data['reason'] ?? null,
                        );
                    }),
                Action::make('adjustStock')
                    ->label('Sesuaikan stok')
                    ->icon('heroicon-o-arrows-right-left')
                    ->visible(fn (Product $record): bool => $record->product_type->tracksInventory())
                    ->form([
                        Select::make('direction')
                            ->label('Arah penyesuaian')
                            ->options(['in' => 'Stok masuk', 'out' => 'Stok keluar'])
                            ->required(),
                        TextInput::make('quantity')->label('Kuantitas')->numeric()->minValue(1)->required(),
                        Textarea::make('reason')->label('Alasan')->required()->maxLength(1000),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $type = $data['direction'] === 'in'
                            ? StockMovementType::AdjustmentIn
                            : StockMovementType::AdjustmentOut;

                        app(AdjustStockAction::class)->handle(
                            $record,
                            auth()->user(),
                            $type,
                            (int) $data['quantity'],
                            $data['reason'],
                        );
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
