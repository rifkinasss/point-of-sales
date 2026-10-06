<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\BrowserTimezone;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
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

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas akun')
                ->description('Identitas dan keadaan akses akun pada platform.')
                ->schema([
                    TextEntry::make('name')->label('Nama')->weight('bold'),
                    TextEntry::make('email')->label('Email'),
                    TextEntry::make('role')->label('Peran')->badge()->formatStateUsing(fn (UserRole $state): string => $state->label()),
                    TextEntry::make('is_active')->label('Status')->badge()
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif')
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    TextEntry::make('created_at')->label('Terdaftar')->dateTime('d M Y, H:i'),
                    TextEntry::make('last_login_at')->label('Aktivitas terakhir')
                        ->state(fn (User $record): string => app(BrowserTimezone::class)->format($record->last_login_at, 'd M Y, H:i') ?? 'Belum pernah masuk'),
                ])->columns(2),
            Section::make('Akses dan organisasi')
                ->schema([
                    TextEntry::make('access_scope')->label('Cakupan akses')->state(function (User $record): string {
                        return match ($record->role) {
                            UserRole::Admin => 'Platform',
                            UserRole::Manager => $record->managedStores()->count().' store',
                            default => $record->store?->name ?? 'Belum ditetapkan',
                        };
                    }),
                    TextEntry::make('store_access')->label('Store yang dapat diakses')->state(function (User $record): string {
                        return match ($record->role) {
                            UserRole::Admin => 'Semua store',
                            UserRole::Manager => $record->managedStores()->pluck('name')->join(', ') ?: 'Belum ada store',
                            default => $record->store?->name ?? 'Belum ada store',
                        };
                    })->columnSpanFull(),
                    TextEntry::make('active_shift')->label('Shift aktif')->state(fn (User $record): string => $record->cashierShifts()->where('status', 'open')->exists() ? 'Ya' : 'Tidak'),
                ])->columns(2),
            Section::make('Aktivitas keamanan')
                ->schema([
                    TextEntry::make('recent_activity')->label('Aktivitas terbaru')->state(function (User $record): string {
                        $timezone = app(BrowserTimezone::class);

                        return AuditLog::withoutGlobalScopes()
                            ->where('user_id', $record->getKey())
                            ->latest()
                            ->limit(5)
                            ->get()
                            ->map(fn (AuditLog $activity): string => $activity->event.' · '.($timezone->format($activity->created_at, 'd M Y, H:i') ?? 'Waktu tidak tersedia'))
                            ->join("\n") ?: 'Belum ada aktivitas yang tercatat.';
                    })->listWithLineBreaks()->columnSpanFull(),
                ]),
        ]);
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
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['store', 'managedStores']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->role === UserRole::Admin;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->role === UserRole::Admin;
    }

    public static function canEdit(Model $record): bool
    {
        $user = self::currentUser();

        return $user instanceof User && $record instanceof User && $user->role === UserRole::Admin;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return array<string, string> */
    public static function assignableOptions(?User $record = null): array
    {
        $user = self::currentUser();

        if ($user?->role !== UserRole::Admin) {
            return [];
        }

        $options = UserRole::assignableOptionsFor(UserRole::Admin);
        if ($record?->role === UserRole::Admin) {
            $options[UserRole::Admin->value] = UserRole::Admin->label();
        }

        return $options;
    }

    private static function currentUser(): ?User
    {
        $user = Filament::auth()->user();

        return $user instanceof User ? $user : null;
    }
}
