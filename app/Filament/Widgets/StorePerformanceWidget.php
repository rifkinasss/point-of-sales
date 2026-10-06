<?php

namespace App\Filament\Widgets;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\Store;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StorePerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Cakupan platform per toko';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['lg' => 4, 'default' => 'full'];

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')->label('Toko')->searchable()->weight('medium'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('plan')->label('Paket')->badge()->color(fn (string $state): string => $state === 'free' ? 'gray' : 'primary'),
                TextColumn::make('active_users_count')->label('Pengguna aktif')->numeric(),
                TextColumn::make('products_count')->label('Produk')->numeric(),
                TextColumn::make('active_shifts_count')->label('Shift aktif')->numeric(),
            ])
            ->emptyStateHeading('Belum ada toko terdaftar')
            ->emptyStateDescription('Tambahkan store melalui administrasi platform untuk melihat cakupannya di sini.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->defaultSort('name')
            ->paginated([5, 10]);
    }

    /** @return Builder<Store> */
    protected function getTableQuery(): Builder
    {
        return Store::query()
            ->withCount([
                'users as active_users_count' => fn (Builder $query): Builder => $query->where('is_active', true),
                'products',
                'cashierShifts as active_shifts_count' => fn (Builder $query): Builder => $query->where('status', ShiftStatus::Open),
            ]);
    }
}
