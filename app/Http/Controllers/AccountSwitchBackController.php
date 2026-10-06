<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PostLoginRedirector;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Packstub\AccountSwitcher\AccountSwitcher;

class AccountSwitchBackController
{
    public function __invoke(AccountSwitcher $switcher): RedirectResponse
    {
        if (! $switcher->isImpersonating()) {
            $user = auth()->user();

            return $user instanceof User
                ? redirect()->to(app(PostLoginRedirector::class)->pathFor($user))
                : redirect()->route('auth.login');
        }

        $impersonator = $switcher->impersonator();
        $adminPanel = Filament::getPanel('admin');

        if (! $impersonator instanceof User
            || ! $impersonator->is_active
            || $impersonator->role !== UserRole::Admin
            || ! $adminPanel
            || ! $impersonator->canAccessPanel($adminPanel)) {
            session()->forget(AccountSwitcher::SESSION_KEY);
            auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('auth.login');
        }

        $switcher->stopImpersonating();

        return redirect()->to('/admin');
    }
}
