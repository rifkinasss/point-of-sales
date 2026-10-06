<?php

namespace App\Filament\Widgets;

use App\Enums\PaymentStatus;
use App\Enums\ShiftStatus;
use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\Payment;
use App\Models\Store;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -6;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getStats(): array
    {
        $storeCount = Store::query()->where('status', StoreStatus::Active)->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $activeManagers = User::query()->where('role', UserRole::Manager)->where('is_active', true)->count();
        $activeCashiers = User::query()->where('role', UserRole::Cashier)->where('is_active', true)->count();
        $activeShifts = CashierShift::query()->where('status', ShiftStatus::Open)->count();
        $pendingPayments = Payment::query()->where('status', PaymentStatus::Pending)->count();

        return [
            Stat::make('Toko aktif', number_format($storeCount))
                ->description($storeCount > 0 ? 'Store yang siap beroperasi' : 'Belum ada store aktif')
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('Pengguna aktif', number_format($activeUsers))
                ->description($activeUsers > 0 ? 'Akun yang dapat masuk' : 'Belum ada pengguna aktif')
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make('Manager aktif', number_format($activeManagers))
                ->description($activeManagers > 0 ? 'Akun pengelola store' : 'Belum ada Manager aktif')
                ->icon('heroicon-o-identification')
                ->color('gray'),
            Stat::make('Cashier aktif', number_format($activeCashiers))
                ->description($activeCashiers > 0 ? 'Akun operator kasir' : 'Belum ada Cashier aktif')
                ->icon('heroicon-o-calculator')
                ->color('gray'),
            Stat::make('Shift aktif', number_format($activeShifts))
                ->description($activeShifts > 0 ? 'Kasir yang sedang bertugas' : 'Belum ada shift terbuka')
                ->icon('heroicon-o-clock')
                ->color('success'),
            Stat::make('Pembayaran tertunda', number_format($pendingPayments))
                ->description($pendingPayments > 0 ? 'Memerlukan perhatian' : 'Belum ada pembayaran tertunda')
                ->icon('heroicon-o-credit-card')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int|array|null
    {
        return ['@xl' => 6, '@lg' => 3, '@sm' => 2, '!@sm' => 1];
    }
}
