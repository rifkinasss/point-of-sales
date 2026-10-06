<?php

namespace App\Filament\Resources\AuditLogs;

use App\Enums\UserRole;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Models\AuditLog;
use App\Services\BrowserTimezone;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|UnitEnum|null $navigationGroup = 'Security & Audit';
    protected static ?string $navigationLabel = 'Audit aktivitas';
    protected static ?int $navigationSort = 30;
    public static function canAccess(): bool { return auth()->user()?->role === UserRole::Admin; }
    public static function form(Schema $schema): Schema { return $schema; }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aktivitas')
                ->schema([
                    TextEntry::make('event')->label('Aktivitas')->badge()
                        ->formatStateUsing(fn (?string $state): string => str($state ?: 'Aktivitas sistem')->replace('_', ' ')->title()->toString()),
                    TextEntry::make('summary')->label('Ringkasan')->state(fn (AuditLog $record): string => self::eventSummary($record))->columnSpanFull(),
                    TextEntry::make('created_at')->label('Waktu')->state(fn (AuditLog $record): string => app(BrowserTimezone::class)->format($record->created_at, 'd M Y, H:i:s') ?? 'Waktu tidak tersedia'),
                    TextEntry::make('user.name')->label('Pelaku')->placeholder('Sistem'),
                    TextEntry::make('ip_address')->label('Alamat IP')->placeholder('Tidak tersedia'),
                ])->columns(2),
            Section::make('Target')
                ->schema([
                    TextEntry::make('auditable_type')->label('Resource')->formatStateUsing(fn (?string $state): string => class_basename((string) $state)),
                    TextEntry::make('resource_reference')->label('Referensi')->state(fn (AuditLog $record): string => $record->resourceReference()),
                    TextEntry::make('store.name')->label('Store')->placeholder('Platform / tidak terkait'),
                    TextEntry::make('url')->label('Request URL')->placeholder('Tidak tersedia')->columnSpanFull()->copyable(),
                ])->columns(2),
            Section::make('Perubahan')
                ->schema([
                    KeyValueEntry::make('old_values')->label('Sebelum')->state(fn (AuditLog $record): array => $record->safeOldValues())->columnSpan(1),
                    KeyValueEntry::make('new_values')->label('Sesudah')->state(fn (AuditLog $record): array => $record->safeNewValues())->columnSpan(1),
                ])->columns(2),
        ]);
    }

    private static function eventSummary(AuditLog $record): string
    {
        $actor = $record->user?->name ?? 'Sistem';
        $target = $record->resourceReference();

        return match (true) {
            str($record->event)->contains(['login', 'logout']) => $actor.' melakukan '.$record->event.'.',
            filled($target) => $actor.' melakukan '.str($record->event)->replace('_', ' ').' pada '.$target.'.',
            default => $actor.' melakukan '.str($record->event)->replace('_', ' ').'.',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y, H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Pelaku')->placeholder('Sistem')->searchable()->sortable(),
                TextColumn::make('event')->label('Aktivitas')->badge()->searchable(),
                TextColumn::make('auditable_type')->label('Resource')->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('resource_reference')->label('Referensi')->state(fn (AuditLog $record): string => $record->resourceReference())->wrap(),
                TextColumn::make('store.name')->label('Store')->placeholder('Platform'),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')->label('Pelaku')->relationship('user', 'name')->searchable()->preload(),
                SelectFilter::make('event')->label('Aktivitas')->options(fn (): array => AuditLog::withoutGlobalScopes()->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('auditable_type')->label('Resource')->options(fn (): array => AuditLog::withoutGlobalScopes()->distinct()->orderBy('auditable_type')->pluck('auditable_type')->mapWithKeys(fn (string $type): array => [$type => class_basename($type)])->all()),
                SelectFilter::make('store_id')->label('Store')->relationship('store', 'name')->searchable()->preload(),
                Filter::make('date_range')->form([
                    DatePicker::make('from')->label('Dari'),
                    DatePicker::make('until')->label('Sampai'),
                ])->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
                }),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->with(['user', 'store']);
    }

    public static function getPages(): array { return ['index' => ListAuditLogs::route('/'), 'view' => ViewAuditLog::route('/{record}')]; }
    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }
}
