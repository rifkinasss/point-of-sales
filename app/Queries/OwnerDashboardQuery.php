<?php

namespace App\Queries;

use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;

class OwnerDashboardQuery
{
    /** @return array{revenue: int, transactions: int, average: int, lowStock: int, paymentMethods: array<string, int>} */
    public function summary(User $owner): array
    {
        $start = now()->startOfMonth();
        $sales = Sale::query()->where('store_id', $owner->store_id)->where('status', SaleStatus::Paid)->where('completed_at', '>=', $start);
        $transactions = (clone $sales)->count();
        $revenue = (int) (clone $sales)->sum('grand_total');
        $saleIds = (clone $sales)->pluck('id');

        return [
            'revenue' => $revenue,
            'transactions' => $transactions,
            'average' => $transactions > 0 ? intdiv($revenue, $transactions) : 0,
            'lowStock' => Product::query()->where('store_id', $owner->store_id)->where('is_active', true)->whereHas('inventory', fn ($query) => $query->whereColumn('quantity', '<=', 'products.minimum_stock'))->count(),
            'paymentMethods' => Payment::query()->whereIn('sale_id', $saleIds)->where('status', PaymentStatus::Completed)->selectRaw('method, SUM(amount_due) as total')->groupBy('method')->pluck('total', 'method')->map(fn ($total): int => (int) $total)->all(),
        ];
    }
}
