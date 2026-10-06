<?php

namespace App\Filament\Resources\Stores;

use App\Actions\Store\SetStoreStatusAction;
use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Filament\Resources\Stores\Pages\ViewStore;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Services\BrowserTimezone;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
    protected static string|UnitEnum|null $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Toko';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas store')
                ->description('Status dan identitas platform yang digunakan untuk mengenali store ini.')
                ->schema([
                    TextEntry::make('name')->label('Nama toko')->weight('bold'),
                    TextEntry::make('store_code')->label('Kode toko')->copyable(),
                    TextEntry::make('branch_code')->label('Kode cabang')->placeholder('Tidak diatur'),
                    TextEntry::make('branch_name')->label('Nama cabang')->placeholder('Tidak diatur'),
                    TextEntry::make('status')->label('Status')->badge()->formatStateUsing(fn (StoreStatus $state): string => $state->label())
                        ->color(fn (StoreStatus $state): string => $state === StoreStatus::Active ? 'success' : 'gray'),
                    TextEntry::make('address')->label('Alamat')->placeholder('Tidak diatur')->columnSpanFull(),
                    TextEntry::make('phone')->label('Telepon')->placeholder('Tidak diatur'),
                ])->columns(2),
            Section::make('Snapshot operasional')
                ->description('Ringkasan keadaan store saat ini, bukan laporan performa bisnis.')
                ->schema([
                    TextEntry::make('active_users')->label('Pengguna aktif')->state(fn (Store $record): string => (string) $record->users()->where('is_active', true)->count()),
                    TextEntry::make('managers_count')->label('Manager')->state(fn (Store $record): string => (string) $record->managers()->count()),
                    TextEntry::make('cashiers_count')->label('Kasir')->state(fn (Store $record): string => (string) $record->users()->where('role', UserRole::Cashier)->count()),
                    TextEntry::make('products_count')->label('Produk aktif')->state(fn (Store $record): string => (string) $record->products()->where('is_active', true)->count()),
                    TextEntry::make('active_shifts')->label('Shift aktif')->state(fn (Store $record): string => (string) $record->cashierShifts()->where('status', ShiftStatus::Open)->count()),
                ])->columns(2),
            Section::make('Akses dan subscription')
                ->schema([
                    TextEntry::make('owner')->label('Owner')->state(fn (Store $record): string => $record->users()->where('role', UserRole::Owner)->value('name') ?? 'Tidak ditemukan'),
                    TextEntry::make('managers')->label('Manager')->state(fn (Store $record): string => $record->managers()->pluck('name')->join(', ') ?: 'Belum ada manager yang ditugaskan'),
                    TextEntry::make('cashiers')->label('Kasir')->state(fn (Store $record): string => $record->users()->where('role', UserRole::Cashier)->pluck('name')->join(', ') ?: 'Belum ada kasir yang ditugaskan')->columnSpanFull(),
                    TextEntry::make('plan')->label('Paket')->placeholder('Belum diatur'),
                    TextEntry::make('subscription')->label('Status subscription')->state(fn (Store $record): string => $record->latestSubscription?->status ?? 'Belum ada subscription'),
                    TextEntry::make('subscription_started_at')->label('Mulai subscription')->state(fn (Store $record): string => app(BrowserTimezone::class)->format($record->latestSubscription?->starts_at, 'd M Y') ?? 'Belum tersedia'),
                ])->columns(2),
            Section::make('Aktivitas platform')
                ->schema([
                    TextEntry::make('recent_activity')->label('Aktivitas terbaru')->state(function (Store $record): string {
                        $timezone = app(BrowserTimezone::class);

                        return AuditLog::withoutGlobalScopes()
                            ->where('store_id', $record->getKey())
                            ->latest()
                            ->limit(5)
                            ->get()
                            ->map(fn (AuditLog $activity): string => $activity->event.' · '.($timezone->format($activity->created_at, 'd M Y, H:i') ?? 'Waktu tidak tersedia'))
                            ->join("\n") ?: 'Belum ada aktivitas administrasi yang tercatat untuk store ini.';
                    })->listWithLineBreaks()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Toko')->searchable()->sortable(),
                TextColumn::make('store_code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('branch_code')->label('Cabang')->searchable()->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (StoreStatus $state): string => $state->label())->color(fn (StoreStatus $state): string => $state === StoreStatus::Active ? 'success' : 'gray'),
                TextColumn::make('plan')->label('Paket')->badge()->placeholder('-'),
                TextColumn::make('latestSubscription.status')->label('Subscription')->badge()->placeholder('Belum ada'),
                TextColumn::make('active_users_count')->label('Pengguna aktif')->counts(['users as active_users_count' => fn (Builder $query): Builder => $query->where('is_active', true)]),
                TextColumn::make('active_shifts_count')->label('Shift aktif')->counts(['cashierShifts as active_shifts_count' => fn (Builder $query): Builder => $query->where('status', ShiftStatus::Open)]),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(StoreStatus::cases())->mapWithKeys(fn (StoreStatus $status): array => [$status->value => $status->label()])->all()),
                SelectFilter::make('plan')->options(collect(config('pos-plans'))->mapWithKeys(fn (array $plan, string $key): array => [$key => $plan['label']])->all()),
                SelectFilter::make('subscription_status')->label('Subscription')->relationship('latestSubscription', 'status'),
                TernaryFilter::make('has_active_shift')->label('Shift aktif')->queries(
                    true: fn (Builder $query): Builder => $query->whereHas('cashierShifts', fn (Builder $shift): Builder => $shift->where('status', ShiftStatus::Open)),
                    false: fn (Builder $query): Builder => $query->whereDoesntHave('cashierShifts', fn (Builder $shift): Builder => $shift->where('status', ShiftStatus::Open)),
                ),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Nonaktifkan store?')
                    ->modalDescription('Store tidak dapat digunakan untuk operasi baru setelah dinonaktifkan. Data historis tetap tersedia.')
                    ->visible(fn (Store $record): bool => $record->status === StoreStatus::Active)
                    ->action(function (Store $record): void {
                        $admin = Filament::auth()->user();
                        abort_unless($admin instanceof User, 403);
                        app(SetStoreStatusAction::class)->execute($admin, $record, StoreStatus::Inactive);
                        Notification::make()->success()->title('Store dinonaktifkan')->send();
                    }),
                Action::make('activate')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->visible(fn (Store $record): bool => $record->status === StoreStatus::Inactive)
                    ->action(function (Store $record): void {
                        $admin = Filament::auth()->user();
                        abort_unless($admin instanceof User, 403);
                        app(SetStoreStatusAction::class)->execute($admin, $record, StoreStatus::Active);
                        Notification::make()->success()->title('Store diaktifkan')->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return Filament::auth()->user() instanceof User && Filament::auth()->user()->role === UserRole::Admin;
    }

    public static function canView(Model $record): bool
    {
        return self::canViewAny() && $record instanceof Store;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit(Model $record): bool { return false; }
    public static function canDelete(Model $record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => ListStores::route('/'),
            'view' => ViewStore::route('/{record}'),
        ];
    }
}
