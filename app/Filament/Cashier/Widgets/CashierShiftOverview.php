<?php

namespace App\Filament\Cashier\Widgets;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Sale;
use App\Models\User;
use App\Services\CashBalanceCalculator;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashierShiftOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected function getStats(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $shift = CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->latest('opened_at')
            ->first();

        if (! $shift) {
            return [
                Stat::make('Shift', 'Belum dibuka')
                    ->description('Buka shift untuk mulai menerima transaksi')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning'),
            ];
        }

        $sales = Sale::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('status', SaleStatus::Paid);
        $summary = app(CashBalanceCalculator::class)->summary($shift);

        return [
            Stat::make('Shift aktif', $shift->opened_at?->format('H:i') ?? '—')
                ->description('Mulai hari ini')
                ->icon('heroicon-o-lock-open')
                ->color('success'),
            Stat::make('Transaksi lunas', number_format((clone $sales)->count()))
                ->description('Pada shift ini')
                ->icon('heroicon-o-receipt-percent')
                ->color('primary'),
            Stat::make('Omzet shift', 'Rp '.number_format((int) (clone $sales)->sum('grand_total'), 0, ',', '.'))
                ->description('Penjualan berhasil')
                ->icon('heroicon-o-banknotes')
                ->color('info'),
            Stat::make('Kas yang diharapkan', 'Rp '.number_format($summary['expected'], 0, ',', '.'))
                ->description('Sebelum penghitungan fisik')
                ->icon('heroicon-o-calculator')
                ->color('warning'),
        ];
    }
}
