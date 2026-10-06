<?php

namespace App\Filament\Widgets;

use App\Enums\StoreStatus;
use App\Enums\UserRole;
use App\Models\Store;
use App\Models\User;
use Filament\Widgets\Widget;

class PlatformDistributionWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = ['lg' => 2, 'default' => 'full'];

    protected string $view = 'filament.widgets.platform-distribution';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Admin;
    }

    protected function getViewData(): array
    {
        $activeUsers = User::query()->where('is_active', true);

        return [
            'roles' => collect(UserRole::cases())->mapWithKeys(fn (UserRole $role): array => [
                $role->label() => (clone $activeUsers)->where('role', $role)->count(),
            ]),
            'stores' => collect(StoreStatus::cases())->mapWithKeys(fn (StoreStatus $status): array => [
                $status->label() => Store::query()->where('status', $status)->count(),
            ]),
        ];
    }
}
