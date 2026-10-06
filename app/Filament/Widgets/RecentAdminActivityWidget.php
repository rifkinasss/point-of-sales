<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Store;
use App\Models\StoreSubscription;
use App\Models\User;
use App\Services\BrowserTimezone;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAdminActivityWidget extends TableWidget
{
    protected static ?string $heading = 'Aktivitas Admin terbaru';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['lg' => 2, 'default' => 'full'];

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('event')
                    ->label('Aktivitas')
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->badge()
                    ->color('primary'),
                TextColumn::make('user.name')->label('Pelaku')->placeholder('Sistem')->default('Sistem'),
                TextColumn::make('auditable_type')
                    ->label('Target')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'Platform'),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($state): string => app(BrowserTimezone::class)->format($state, 'd M Y · H:i') ?? 'Waktu tidak tersedia'),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label('Lihat semua')
                    ->url(route('filament.admin.resources.audit-logs.index'))
                    ->link(),
            ])
            ->emptyStateHeading('Belum ada aktivitas administrasi terbaru')
            ->emptyStateDescription('Perubahan akses, store, dan keamanan platform akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    /** @return Builder<AuditLog> */
    protected function getTableQuery(): Builder
    {
        return AuditLog::query()
            ->withoutGlobalScopes()
            ->with(['user', 'store'])
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('event', [
                        'created',
                        'updated',
                        'user_access_created',
                        'user_access_updated',
                        'user_password_reset_requested',
                        'store_status_changed',
                        'account_switched',
                        'subscription_created',
                        'subscription_updated',
                        'announcement_created',
                        'announcement_updated',
                    ])
                    ->where(function (Builder $query): void {
                        $query
                            ->whereIn('auditable_type', [User::class, Store::class, StoreSubscription::class])
                            ->orWhere('event', 'account_switched');
                    });
            });
    }
}
