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

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getStats(): array
    {
        $storeCount = Store::query()->where('status', StoreStatus::Active)->count();
        $activeUsers = User::query()->where('is_active', true)->count();
        $activeShifts = CashierShift::query()->where('status', ShiftStatus::Open)->count();
        $pendingPayments = Payment::query()->where('status', PaymentStatus::Pending)->count();

        return [
            Stat::make('Toko aktif', number_format($storeCount))
                ->description('Store yang siap beroperasi')
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),
            Stat::make('Pengguna aktif', number_format($activeUsers))
                ->description('Semua akun yang dapat masuk')
                ->icon('heroicon-o-user-group')
                ->color('info'),
            Stat::make('Shift aktif', number_format($activeShifts))
                ->description('Kasir yang sedang bertugas')
                ->icon('heroicon-o-clock')
                ->color('success'),
            Stat::make('Pembayaran tertunda', number_format($pendingPayments))
                ->description('Memerlukan perhatian')
                ->icon('heroicon-o-credit-card')
                ->color('warning'),
        ];
    }
}
