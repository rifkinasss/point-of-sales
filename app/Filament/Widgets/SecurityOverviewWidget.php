<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Widgets\Widget;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Smony\FilamentUserSessions\Models\Session;

class SecurityOverviewWidget extends Widget
{
    protected static ?int $sort = -5;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.security-overview';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getViewData(): array
    {
        $onlineSince = now()->subMinutes(config('filament-user-sessions.online_threshold_minutes', 5))->timestamp;
        $accessEvents = ['user_access_created', 'user_access_updated', 'user_password_reset_requested', 'store_status_changed'];
        $activeSessions = Session::query()->where('last_activity', '>=', $onlineSince)->count();
        $activeAdmins = User::query()->where('role', UserRole::Admin)->where('is_active', true)->count();
        $accessEventCount = AuditLog::withoutGlobalScopes()->whereIn('event', $accessEvents)->whereDate('created_at', today())->count();
        $accountSwitches = AccountSwitch::query()->where('created_at', '>=', now()->subDays(30))->count();

        return [
            'metrics' => [
                ['label' => 'Sesi aktif', 'value' => $activeSessions, 'icon' => 'heroicon-o-computer-desktop', 'empty' => 'Belum ada sesi aktif'],
                ['label' => 'Admin aktif', 'value' => $activeAdmins, 'icon' => 'heroicon-o-shield-check', 'empty' => 'Tidak ada Admin aktif'],
                ['label' => 'Akses berubah', 'value' => $accessEventCount, 'icon' => 'heroicon-o-key', 'empty' => 'Belum ada perubahan akses hari ini'],
                ['label' => 'Account switch 30 hari', 'value' => $accountSwitches, 'icon' => 'heroicon-o-arrow-path', 'empty' => 'Belum ada account switch'],
            ],
        ];
    }
}
