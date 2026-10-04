<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\User;
use App\Services\PostLoginRedirector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(PostLoginRedirector $redirector): View|RedirectResponse
    {
        if (Auth::check()) {
            /** @var User $user */
            $user = Auth::user();

            return redirect()->to($redirector->pathFor($user));
        }

        return view('auth.login');
    }

    public function store(Request $request, PostLoginRedirector $redirector): RedirectResponse
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

        return redirect()->to($redirector->pathFor($user));
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

}
