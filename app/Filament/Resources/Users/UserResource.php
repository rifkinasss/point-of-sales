<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('role', '!=', UserRole::Admin->value);
        $user = self::currentUser();

        if ($user instanceof User && $user->role === UserRole::Owner) {
            $query
                ->where('store_id', $user->store_id)
                ->whereIn('role', [UserRole::Manager->value, UserRole::Cashier->value]);
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->role->canAccessAdmin() && self::currentUser()?->role !== UserRole::Manager;
    }

    public static function canCreate(): bool
    {
        return self::assignableOptions() !== [];
    }

    public static function canEdit(Model $record): bool
    {
        $user = self::currentUser();

        return $user instanceof User
            && $record instanceof User
            && ($user->role !== UserRole::Owner || $record->store_id === $user->store_id)
            && array_key_exists($record->role->value, UserRole::assignableOptionsFor($user->role));
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public static function assignableOptions(): array
    {
        $user = self::currentUser();

        return $user instanceof User ? UserRole::assignableOptionsFor($user->role) : [];
    }

    private static function currentUser(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }
}
