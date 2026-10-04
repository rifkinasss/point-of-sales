<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\ShiftStatus;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return redirect()->to($this->panelPath($user->role));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attemptWhen($credentials, fn (User $user): bool => $user->is_active)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->to($this->panelPath($user->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && $user->role === UserRole::Cashier && CashierShift::query()
            ->where('user_id', $user->id)
            ->where('status', ShiftStatus::Open)
            ->exists()) {
            return redirect('/cashier/close-shift')
                ->with('shift_guard', 'Tutup shift dan hitung kas fisik terlebih dahulu sebelum keluar.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    private function panelPath(UserRole $role): string
    {
        return match ($role) {
            UserRole::Admin => '/admin',
            UserRole::Owner => '/owner',
            UserRole::Manager => '/manager',
            UserRole::Cashier => CashierShift::query()
                ->where('user_id', auth()->id())
                ->where('status', ShiftStatus::Open)
                ->exists() ? '/cashier/pos' : '/cashier/open-shift',
        };
    }
}
