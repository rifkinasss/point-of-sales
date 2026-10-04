<?php

namespace App\Filament\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge(),
                TextColumn::make('quantity_change')
                    ->label('Perubahan')
                    ->numeric(),
                TextColumn::make('quantity_before')
                    ->label('Sebelum')
                    ->numeric(),
                TextColumn::make('quantity_after')
                    ->label('Sesudah')
                    ->numeric(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('performedBy.name')
                    ->label('Pelaku')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('product')->relationship('product', 'name')->label('Produk'),
                SelectFilter::make('type')->options(StockMovementType::class)->label('Tipe mutasi'),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
