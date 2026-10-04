<?php

namespace App\Filament\Resources\Stores;

use App\Enums\UserRole;
use App\Filament\Resources\Stores\Pages\CreateStore;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Models\Store;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Toko';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama toko')->required()->maxLength(150)->live(onBlur: true)
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
            TextInput::make('slug')->label('Slug URL')->required()->alphaDash()->maxLength(150)->unique(ignoreRecord: true),
            TextInput::make('phone')->label('Telepon')->tel()->maxLength(30),
            TextInput::make('email')->email()->maxLength(255),
            Select::make('plan')->label('Paket')->options(collect(config('pos-plans'))->mapWithKeys(fn (array $plan, string $key): array => [$key => $plan['label']])->all())->required()->live()
                ->afterStateUpdated(function (string $state, callable $set): void {
                    $plan = config("pos-plans.{$state}");
                    $set('product_limit', $plan['product_limit']);
                    $set('staff_limit', $plan['staff_limit']);
                }),
            TextInput::make('product_limit')->label('Batas produk aktif')->numeric()->minValue(0)->helperText('0 berarti tanpa batas.'),
            TextInput::make('staff_limit')->label('Batas staf')->numeric()->minValue(0)->helperText('0 berarti tanpa batas.'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Toko')->searchable()->sortable(),
            TextColumn::make('slug')->label('URL')->copyable(),
            TextColumn::make('users_count')->label('Pengguna')->counts('users'),
            TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
        ])->recordActions([EditAction::make()]);
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user() instanceof User && Filament::auth()->user()->role === UserRole::Admin;
    }

    public static function getPages(): array
    {
        return ['index' => ListStores::route('/'), 'create' => CreateStore::route('/create'), 'edit' => EditStore::route('/{record}/edit')];
    }
}
