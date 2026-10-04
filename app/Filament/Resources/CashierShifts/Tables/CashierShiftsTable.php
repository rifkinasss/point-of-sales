<?php

namespace App\Filament\Resources\CashierShifts\Tables;

use App\Enums\ShiftStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashierShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('opened_at')
                    ->label('Dibuka')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Ditutup')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('opening_cash')
                    ->label('Kas awal')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('expected_cash')
                    ->label('Kas ekspektasi')
                    ->money('IDR', locale: 'id')
                    ->placeholder('-'),
                TextColumn::make('closing_cash_actual')
                    ->label('Kas fisik')
                    ->money('IDR', locale: 'id')
                    ->placeholder('-'),
                TextColumn::make('cash_difference')
                    ->label('Selisih')
                    ->money('IDR', locale: 'id')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')->options(ShiftStatus::class)->label('Status'),
                SelectFilter::make('cashier')->relationship('cashier', 'name')->label('Kasir'),
            ])
            ->defaultSort('opened_at', 'desc');
    }
}
