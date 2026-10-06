<?php

namespace App\Filament\Widgets\Archive;

use App\Enums\SaleStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaySalesStats extends StatsOverviewWidget
{
    protected static ?int $sort = -4;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $sales = Sale::query()
            ->where('status', SaleStatus::Paid)
            ->where('completed_at', '>=', $today);
        $transactionCount = (clone $sales)->count();
        $revenue = (int) (clone $sales)->sum('grand_total');
        $itemsSold = (int) SaleItem::query()
            ->whereHas('sale', fn ($query) => $query
                ->where('status', SaleStatus::Paid)
                ->where('completed_at', '>=', $today))
            ->sum('quantity');

        return [
            Stat::make('Omzet hari ini', 'Rp '.number_format($revenue, 0, ',', '.'))
                ->description('Penjualan sejak pukul 00.00')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Transaksi lunas', number_format($transactionCount))
                ->description('Pembayaran telah diterima')
                ->icon('heroicon-o-receipt-percent')
                ->color('primary'),
            Stat::make('Rata-rata transaksi', 'Rp '.number_format($transactionCount ? intdiv($revenue, $transactionCount) : 0, 0, ',', '.'))
                ->description('Nilai per transaksi')
                ->icon('heroicon-o-chart-bar-square')
                ->color('warning'),
            Stat::make('Item terjual', number_format($itemsSold))
                ->description('Total unit hari ini')
                ->icon('heroicon-o-shopping-bag')
                ->color('info'),
        ];
    }
}
