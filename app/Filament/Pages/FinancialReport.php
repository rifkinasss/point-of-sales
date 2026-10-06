<?php

namespace App\Filament\Pages;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReport extends Page
{
    protected string $view = 'filament.pages.financial-report';

    protected static ?string $navigationLabel = 'Laporan keuangan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    public string $dateFrom = '';

    public string $dateUntil = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateUntil = now()->toDateString();
    }

    public function getSummaryProperty(): array
    {
        $start = Carbon::parse($this->dateFrom)->startOfDay();
        $end = Carbon::parse($this->dateUntil)->endOfDay();
        $sales = Sale::query()->where('status', SaleStatus::Paid)->whereBetween('completed_at', [$start, $end]);
        $revenue = (int) (clone $sales)->sum('grand_total');
        $ids = (clone $sales)->pluck('id');
        $cogs = (int) SaleItem::query()
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIn('sale_items.sale_id', $ids)
            ->sum(DB::raw('sale_items.quantity * COALESCE(products.cost_price, 0)'));
        $expenses = (int) Expense::query()->whereBetween('occurred_at', [$start, $end])->sum('amount');

        $paymentMethods = Payment::query()
            ->whereHas('sale', fn ($query) => $query->where('status', SaleStatus::Paid)->whereBetween('completed_at', [$start, $end]))
            ->selectRaw('method, SUM(amount_due) as total')
            ->groupBy('method')
            ->pluck('total', 'method')
            ->map(fn ($total): int => (int) $total)
            ->all();

        return compact('revenue', 'cogs', 'expenses', 'paymentMethods') + ['grossProfit' => $revenue - $cogs, 'netProfit' => $revenue - $cogs - $expenses, 'netCashFlow' => $revenue - $expenses];
    }

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && in_array($user->role, [UserRole::Owner, UserRole::Manager], true);
    }

    public function downloadCsv(): StreamedResponse
    {
        $summary = $this->summary;
        return response()->streamDownload(function () use ($summary): void { $out = fopen('php://output', 'w'); fputcsv($out, ['Komponen', 'Nominal']); foreach (['Omzet' => $summary['revenue'], 'HPP estimasi' => $summary['cogs'], 'Pengeluaran' => $summary['expenses'], 'Laba kotor' => $summary['grossProfit'], 'Arus kas bersih' => $summary['netCashFlow'], 'Laba bersih' => $summary['netProfit']] as $name => $amount) fputcsv($out, [$name, $amount]); foreach ($summary['paymentMethods'] as $method => $amount) fputcsv($out, ['Penerimaan '.str_replace('_', ' ', $method), $amount]); fclose($out); }, 'laporan-keuangan-'.$this->dateFrom.'-'.$this->dateUntil.'.csv');
    }
}
