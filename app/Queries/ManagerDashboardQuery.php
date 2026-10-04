<?php

namespace App\Queries;

use App\Enums\SaleStatus;
use App\Enums\ShiftStatus;
use App\Models\CashierShift;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

class ManagerDashboardQuery
{
    /** @return array<string, int> */
    public function summary(User $manager): array
    {
        $today = now()->startOfDay();
        $sales = Sale::query()->where('store_id', $manager->store_id)->where('status', SaleStatus::Paid)->where('completed_at', '>=', $today);

        return [
            'salesToday' => (clone $sales)->count(),
            'revenueToday' => (int) (clone $sales)->sum('grand_total'),
            'openShifts' => CashierShift::query()->where('store_id', $manager->store_id)->where('status', ShiftStatus::Open)->count(),
            'lowStock' => Product::query()->where('store_id', $manager->store_id)->where('is_active', true)->whereHas('inventory', fn ($query) => $query->whereColumn('quantity', '<=', 'products.minimum_stock'))->count(),
        ];
    }

    public function recentSales(User $manager)
    {
        return Sale::query()->with('cashier')->where('store_id', $manager->store_id)->latest()->limit(8)->get();
    }
}
