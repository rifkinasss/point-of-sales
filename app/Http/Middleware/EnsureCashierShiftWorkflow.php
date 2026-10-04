<?php

namespace App\Http\Middleware;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\CashierShift;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashierShiftWorkflow
{
    /**
     * Keep a cashier in the accountable order: open shift → operate POS → close shift → sign out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->role !== UserRole::Cashier) {
            return $next($request);
        }

        $isOpenShiftPage = $request->is('cashier/open-shift');
        $isLogoutRoute = $request->is('cashier/logout') || $request->routeIs('filament.cashier.auth.logout');
        $hasOpenShift = CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists();

        if ($hasOpenShift) {
            if ($isLogoutRoute) {
                return redirect('/cashier/close-shift')
                    ->with('shift_guard', 'Tutup shift dan hitung kas fisik terlebih dahulu sebelum keluar.');
            }

            if ($isOpenShiftPage) {
                return redirect('/cashier/pos-checkout');
            }

            return $next($request);
        }

        if (! $isOpenShiftPage && ! $isLogoutRoute) {
            return redirect('/cashier/open-shift');
        }

        return $next($request);
    }
}
