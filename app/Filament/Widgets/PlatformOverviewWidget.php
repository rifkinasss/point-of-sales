<?php

namespace App\Filament\Widgets;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -6;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $storeCount = Store::query()->count();
        $newStores = Store::query()->where('created_at', '>=', $monthStart)->count();
        $staffCount = User::query()->whereIn('role', [UserRole::Owner, UserRole::Manager, UserRole::Cashier])->where('is_active', true)->count();
        $monthlyRevenue = (int) Sale::query()
            ->where('status', SaleStatus::Paid)
            ->where('completed_at', '>=', $monthStart)
            ->sum('grand_total');

        return [
            Stat::make('Toko aktif', number_format($storeCount))
                ->description($newStores.' toko baru bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('Staf aktif', number_format($staffCount))
                ->description('Owner, manager, dan kasir')
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make('Omzet platform bulan ini', 'Rp '.number_format($monthlyRevenue, 0, ',', '.'))
                ->description('Akumulasi transaksi lunas')
                ->icon('heroicon-o-chart-bar')
                ->color('success'),
            Stat::make('Paket berbayar', number_format(Store::query()->where('plan', '!=', 'free')->count()))
                ->description('Toko di luar paket Free')
                ->icon('heroicon-o-sparkles')
                ->color('warning'),
        ];
    }
}
