<?php

namespace App\Filament\Pages;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReport extends Page
{
    protected string $view = 'filament.pages.sales-report';

    protected static ?string $navigationLabel = 'Laporan penjualan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?string $title = 'Laporan Penjualan';

    protected static ?int $navigationSort = 10;

    public string $dateFrom = '';

    public string $dateUntil = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateUntil = now()->toDateString();
    }

    /** @return array{revenue: int, transactions: int, items: int, average: int, payments: Collection<int, object>, products: Collection<int, object>, sales: Collection<int, Sale>} */
    public function getReportProperty(): array
    {
        $sales = $this->paidSalesQuery();
        $transactions = (clone $sales)->count();
        $revenue = (int) (clone $sales)->sum('grand_total');
        $saleIds = (clone $sales)->pluck('id');

        return [
            'revenue' => $revenue,
            'transactions' => $transactions,
            'items' => (int) SaleItem::query()->whereIn('sale_id', $saleIds)->sum('quantity'),
            'average' => $transactions > 0 ? intdiv($revenue, $transactions) : 0,
            'payments' => Payment::query()
                ->selectRaw('method, COUNT(*) as transaction_count, SUM(amount_due) as total')
                ->whereIn('sale_id', $saleIds)
                ->where('status', \App\Enums\PaymentStatus::Completed)
                ->groupBy('method')
                ->orderByDesc('total')
                ->get(),
            'products' => SaleItem::query()
                ->selectRaw('product_name, SUM(quantity) as quantity_sold, SUM(line_total) as revenue')
                ->whereIn('sale_id', $saleIds)
                ->groupBy('product_name')
                ->orderByDesc('quantity_sold')
                ->limit(10)
                ->get(),
            'sales' => (clone $sales)
                ->with(['cashier', 'payments'])
                ->latest('completed_at')
                ->limit(15)
                ->get(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->role === UserRole::Owner;
    }

    public function downloadCsv(): StreamedResponse
    {
        $sales = $this->paidSalesQuery()->with(['cashier', 'payments', 'customer'])->latest('completed_at')->get();
        return response()->streamDownload(function () use ($sales): void { $out = fopen('php://output', 'w'); fputcsv($out, ['Invoice', 'Waktu', 'Kasir', 'Member', 'Metode', 'Total']); foreach ($sales as $sale) fputcsv($out, [$sale->invoice_number, $sale->completed_at?->format('Y-m-d H:i:s'), $sale->cashier?->name, $sale->customer?->name, $sale->payments->pluck('method')->map(fn ($method) => $method->value)->unique()->implode(', '), $sale->grand_total]); fclose($out); }, 'laporan-penjualan-'.$this->dateFrom.'-'.$this->dateUntil.'.csv');
    }

    private function paidSalesQuery(): Builder
    {
        $start = Carbon::parse($this->dateFrom ?: now()->startOfMonth())->startOfDay();
        $end = Carbon::parse($this->dateUntil ?: now())->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return Sale::query()
            ->where('status', SaleStatus::Paid)
            ->whereBetween('completed_at', [$start, $end]);
    }
}
