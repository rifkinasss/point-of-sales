<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Packstub\AccountSwitcher\Models\AccountSwitch;
use Smony\FilamentUserSessions\Models\Session;

class SecurityOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -5;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getStats(): array
    {
        $onlineSince = now()->subMinutes(config('filament-user-sessions.online_threshold_minutes', 5))->timestamp;
        $accessEvents = ['user_access_created', 'user_access_updated', 'user_password_reset_requested', 'store_status_changed'];

        return [
            Stat::make('Sesi aktif', number_format(Session::query()->where('last_activity', '>=', $onlineSince)->count()))
                ->description('Perangkat yang aktif dalam batas waktu sistem')
                ->icon('heroicon-o-computer-desktop')
                ->color('primary'),
            Stat::make('Admin aktif', number_format(User::query()->where('role', UserRole::Admin)->where('is_active', true)->count()))
                ->description('Akun dengan akses platform')
                ->icon('heroicon-o-shield-check')
                ->color('success'),
            Stat::make('Perubahan akses hari ini', number_format(AuditLog::withoutGlobalScopes()->whereIn('event', $accessEvents)->whereDate('created_at', today())->count()))
                ->description('Perubahan user dan status platform')
                ->icon('heroicon-o-key')
                ->color('warning'),
            Stat::make('Account switch 30 hari', number_format(AccountSwitch::query()->where('created_at', '>=', now()->subDays(30))->count()))
                ->description('Impersonation dan perpindahan akun yang tercatat')
                ->icon('heroicon-o-arrow-path')
                ->color('info'),
        ];
    }
}
