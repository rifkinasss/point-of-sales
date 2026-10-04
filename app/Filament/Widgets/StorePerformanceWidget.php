<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Store;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StorePerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Performa toko bulan ini';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

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
                TextColumn::make('plan')->label('Paket')->badge()->color(fn (string $state): string => $state === 'free' ? 'gray' : 'primary'),
                TextColumn::make('users_count')->label('Staf')->numeric(),
                TextColumn::make('products_count')->label('Produk')->numeric(),
                TextColumn::make('monthly_revenue')->label('Omzet bulan ini')->money('IDR', locale: 'id')->sortable(),
            ])
            ->defaultSort('monthly_revenue', 'desc')
            ->paginated([5, 10]);
    }

    /** @return Builder<Store> */
    protected function getTableQuery(): Builder
    {
        return Store::query()
            ->withCount(['users', 'products'])
            ->withSum([
                'sales as monthly_revenue' => fn (Builder $query): Builder => $query
                    ->where('status', SaleStatus::Paid)
                    ->where('completed_at', '>=', now()->startOfMonth()),
            ], 'grand_total');
    }
}
