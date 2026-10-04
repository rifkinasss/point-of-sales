<?php

namespace App\Filament\Resources\CashierShifts;

use App\Filament\Resources\CashierShifts\Pages\ListCashierShifts;
use App\Filament\Resources\CashierShifts\Schemas\CashierShiftForm;
use App\Filament\Resources\CashierShifts\Tables\CashierShiftsTable;
use App\Models\CashierShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CashierShiftResource extends Resource
{
    protected static ?string $model = CashierShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?string $navigationLabel = 'Shift kasir';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'shift kasir';

    protected static ?string $pluralModelLabel = 'shift kasir';

    public static function form(Schema $schema): Schema
    {
        return CashierShiftForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierShiftsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierShifts::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
