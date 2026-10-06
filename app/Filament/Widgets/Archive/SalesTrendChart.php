<?php

namespace App\Filament\Widgets\Archive;

use App\Enums\SaleStatus;
use App\Models\Sale;
use Asharif88\FilamentPlotly\Widgets\PlotlyWidget;
use Carbon\Carbon;

class SalesTrendChart extends PlotlyWidget
{
    protected static ?string $heading = 'Tren omzet 7 hari terakhir';

    protected static ?string $subheading = 'Berdasarkan transaksi yang sudah lunas';

    protected static ?int $sort = -3;

    protected static int $contentHeight = 290;

    protected int|string|array $columnSpan = 'full';

    protected function getChartData(): array
    {
        $dates = collect(range(6, 0))
            ->map(fn (int $daysAgo): string => now()->subDays($daysAgo)->toDateString());

        $totals = Sale::query()
            ->selectRaw('DATE(completed_at) as sale_date, SUM(grand_total) as total')
            ->where('status', SaleStatus::Paid)
            ->whereBetween('completed_at', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        return [[
            'x' => $dates->map(fn (string $date): string => Carbon::parse($date)->translatedFormat('D, d M'))->all(),
            'y' => $dates->map(fn (string $date): int => (int) ($totals[$date] ?? 0))->all(),
            'type' => 'scatter',
            'mode' => 'lines+markers',
            'name' => 'Omzet',
            'line' => ['color' => '#f59e0b', 'width' => 3],
            'marker' => ['color' => '#fbbf24', 'size' => 7],
            'fill' => 'tozeroy',
            'fillcolor' => 'rgba(245, 158, 11, 0.12)',
            'hovertemplate' => 'Rp %{y:,.0f}<extra></extra>',
        ]];
    }

    protected function getChartLayout(): array
    {
        return [
            'margin' => ['l' => 56, 'r' => 20, 't' => 12, 'b' => 48],
            'paper_bgcolor' => 'rgba(0,0,0,0)',
            'plot_bgcolor' => 'rgba(0,0,0,0)',
            'showlegend' => false,
            'yaxis' => ['tickprefix' => 'Rp ', 'rangemode' => 'tozero', 'gridcolor' => 'rgba(148, 163, 184, 0.18)'],
            'xaxis' => ['fixedrange' => true],
        ];
    }

    protected function getChartConfig(): array
    {
        return [
            'displayModeBar' => false,
            'responsive' => true,
        ];
    }

    protected function getDarkThemeLayout(): array
    {
        return [
            'font' => ['color' => '#d1d5db'],
            'xaxis' => ['gridcolor' => 'rgba(148, 163, 184, 0.12)'],
        ];
    }

    protected function getLightThemeLayout(): array
    {
        return [
            'font' => ['color' => '#4b5563'],
            'xaxis' => ['gridcolor' => 'rgba(148, 163, 184, 0.18)'],
        ];
    }
}
