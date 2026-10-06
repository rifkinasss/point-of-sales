<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use Filament\Widgets\Widget;

class AdminQuickActionsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = ['lg' => 2, 'default' => 'full'];

    protected string $view = 'filament.widgets.admin-quick-actions';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getViewData(): array
    {
        return [
            'actions' => [
                ['label' => 'Kelola pengguna', 'description' => 'Atur role dan akses platform.', 'icon' => 'heroicon-o-users', 'url' => route('filament.admin.resources.users.index')],
                ['label' => 'Kelola toko', 'description' => 'Tinjau status dan identitas store.', 'icon' => 'heroicon-o-building-storefront', 'url' => route('filament.admin.resources.stores.index')],
                ['label' => 'Lihat audit aktivitas', 'description' => 'Periksa perubahan admin dan keamanan.', 'icon' => 'heroicon-o-shield-check', 'url' => route('filament.admin.resources.audit-logs.index')],
                ['label' => 'Lihat sesi aktif', 'description' => 'Kelola perangkat yang sedang aktif.', 'icon' => 'heroicon-o-computer-desktop', 'url' => route('filament.admin.resources.security.user-sessions.index')],
            ],
        ];
    }
}
