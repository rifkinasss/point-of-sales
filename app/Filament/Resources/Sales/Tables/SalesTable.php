<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Actions\Sale\RefundSaleAction;
use App\Actions\Sale\VoidSaleAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('subtotal')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('item_discount_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transaction_discount_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Waktu lunas')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(SaleStatus::class)->label('Status'),
                SelectFilter::make('cashier')->relationship('cashier', 'name')->label('Kasir'),
            ])
            ->recordActions([
                Action::make('void')->label('Void transaksi')->icon('heroicon-o-x-circle')->color('warning')->visible(fn ($record) => $record->status === SaleStatus::PendingPayment && in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true))->schema([Textarea::make('reason')->label('Alasan void')->required()->maxLength(1000)])->requiresConfirmation()->action(fn ($record, array $data) => app(VoidSaleAction::class)->handle($record, auth()->user(), $data['reason'])),
                Action::make('refund')->label('Refund penuh')->icon('heroicon-o-arrow-uturn-left')->color('danger')->visible(fn ($record) => $record->status === SaleStatus::Paid && in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true))->schema([Textarea::make('reason')->label('Alasan refund')->required()->maxLength(1000)])->requiresConfirmation()->action(fn ($record, array $data) => app(RefundSaleAction::class)->handle($record, auth()->user(), $data['reason'])),
            ])
            ->defaultSort('completed_at', 'desc');
    }
}
