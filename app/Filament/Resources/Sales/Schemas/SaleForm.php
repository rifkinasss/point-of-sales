<?php

namespace App\Filament\Resources\Sales\Schemas;

use App\Enums\SaleStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_number')
                    ->required()
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Nomor invoice dibuat otomatis dan tidak dapat diubah.'),
                Select::make('cashier_shift_id')
                    ->relationship('cashierShift', 'id')
                    ->required(),
                Select::make('cashier_id')
                    ->relationship('cashier', 'name')
                    ->required(),
                Select::make('status')
                    ->options(SaleStatus::class)
                    ->required(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('item_discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('transaction_discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('grand_total')
                    ->required()
                    ->numeric(),
                Textarea::make('note')
                    ->columnSpanFull(),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
