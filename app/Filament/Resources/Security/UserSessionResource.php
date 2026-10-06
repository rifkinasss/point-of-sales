<?php

namespace App\Filament\Resources\Security;

use App\Enums\UserRole;
use App\Filament\Resources\Security\UserSessionResource\Pages\ListUserSessions;
use Filament\Facades\Filament;
use App\Models\User;
use Smony\FilamentUserSessions\Resources\SessionResource;
use UnitEnum;

class UserSessionResource extends SessionResource
{
    protected static ?string $navigationLabel = 'Sesi pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Security & Audit';

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->is_active && $user->role === UserRole::Admin;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserSessions::route('/'),
        ];
    }
}
