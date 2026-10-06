<?php

namespace App\Filament\Resources\Expenses;

use App\Enums\UserRole;
use App\Filament\Resources\Expenses\Pages\CreateExpense;
use App\Filament\Resources\Expenses\Pages\EditExpense;
use App\Filament\Resources\Expenses\Pages\ListExpenses;
use App\Models\Expense;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ExpenseResource extends Resource
{
    public static function canViewAny(): bool { return false; }
    protected static ?string $model = Expense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Pengeluaran';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')->label('Kategori')->options([
                'Operasional' => 'Operasional',
                'Utilitas' => 'Utilitas',
                'Sewa' => 'Sewa',
                'Gaji' => 'Gaji',
                'Pemasaran' => 'Pemasaran',
                'Lainnya' => 'Lainnya',
            ])->searchable()->required(),
            TextInput::make('amount')->label('Nominal')->numeric()->prefix('Rp')->minValue(1)->required(),
            DateTimePicker::make('occurred_at')->label('Tanggal pengeluaran')->default(now())->required(),
            Textarea::make('description')->label('Keterangan')->required()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('occurred_at')->label('Tanggal')->dateTime('d M Y, H:i')->sortable(),
            TextColumn::make('category')->label('Kategori')->badge(),
            TextColumn::make('description')->label('Keterangan')->limit(50)->searchable(),
            TextColumn::make('amount')->label('Nominal')->money('IDR', locale: 'id')->sortable(),
            TextColumn::make('createdBy.name')->label('Dicatat oleh')->toggleable(),
        ])->defaultSort('occurred_at', 'desc')->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExpenses::route('/'),
            'create' => CreateExpense::route('/create'),
            'edit' => EditExpense::route('/{record}/edit'),
        ];
    }
}
