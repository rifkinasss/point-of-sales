<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Pelanggan';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('Kode pelanggan')->required()->maxLength(30)->unique(ignoreRecord: true),
            TextInput::make('card_number')->label('Nomor kartu / QR member')->maxLength(80)->unique(ignoreRecord: true)->helperText('Nilai yang dibaca scanner barcode atau QR.'),
            TextInput::make('name')->label('Nama')->required()->maxLength(255),
            Select::make('tier')->label('Level member')->options(['regular' => 'Regular', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum'])->default('regular')->required(),
            TextInput::make('phone')->label('Telepon / WhatsApp')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            DatePicker::make('birth_date')->label('Tanggal lahir'),
            TextInput::make('credit_limit')->label('Limit kredit')->numeric()->prefix('Rp')->default(0)->minValue(0),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('address')->label('Alamat')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kode')->searchable(),
            TextColumn::make('tier')->label('Level')->badge(),
            TextColumn::make('name')->label('Pelanggan')->searchable()->sortable(),
            TextColumn::make('phone')->label('Telepon')->searchable(),
            TextColumn::make('points')->label('Poin')->numeric(),
            TextColumn::make('sales_count')->counts('sales')->label('Transaksi')->sortable(),
            TextColumn::make('total_spent')->label('Total belanja')->state(fn (Customer $record): int => (int) $record->sales()->where('status', 'paid')->sum('grand_total'))->money('IDR', locale: 'id'),
            TextColumn::make('credit_balance')->label('Saldo kredit')->money('IDR', locale: 'id'),
            IconColumn::make('is_active')->label('Aktif')->boolean(),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCustomers::route('/'), 'create' => CreateCustomer::route('/create'), 'edit' => EditCustomer::route('/{record}/edit')];
    }
}
