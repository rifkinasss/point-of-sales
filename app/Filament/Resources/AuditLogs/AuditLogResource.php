<?php

namespace App\Filament\Resources\AuditLogs;

use App\Enums\UserRole;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Audit aktivitas';
    protected static ?int $navigationSort = 30;
    public static function canAccess(): bool { return in_array(auth()->user()?->role, [UserRole::Admin, UserRole::Owner, UserRole::Manager], true); }
    public static function form(Schema $schema): Schema { return $schema; }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i:s')->sortable(),
        TextColumn::make('event')->label('Aktivitas')->badge()->searchable(),
        TextColumn::make('auditable_type')->label('Data')->formatStateUsing(fn (string $state) => class_basename($state)),
        TextColumn::make('auditable_id')->label('ID'),
        TextColumn::make('user.name')->label('Pelaku')->placeholder('Sistem'),
        TextColumn::make('ip_address')->label('IP')->toggleable(),
    ])->defaultSort('created_at', 'desc'); }
    public static function getPages(): array { return ['index' => ListAuditLogs::route('/')]; }
    public static function canCreate(): bool { return false; } public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool { return false; } public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool { return false; }
}
