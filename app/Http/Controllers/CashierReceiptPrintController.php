<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashierReceiptPrintController extends Controller
{
    public function __invoke(Request $request, Sale $sale): View
    {
        $user = User::query()->find($request->user()?->getAuthIdentifier());
        abort_unless($user instanceof User && $user->role === UserRole::Cashier && $user->is_active, 403);

        $sale = Sale::query()
            ->with(['items', 'payments', 'cashier', 'customer', 'store'])
            ->whereKey($sale->id)
            ->where('cashier_id', $user->id)
            ->where('store_id', $user->store_id)
            ->where('status', SaleStatus::Paid)
            ->firstOrFail();

        return view('cashier.receipt-print', compact('sale'));
    }
}
