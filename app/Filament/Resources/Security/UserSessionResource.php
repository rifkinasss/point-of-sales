<?php

namespace App\Filament\Resources\Security;

use App\Filament\Resources\Security\UserSessionResource\Pages\ListUserSessions;
use Smony\FilamentUserSessions\Resources\SessionResource;
use UnitEnum;

class UserSessionResource extends SessionResource
{
    protected static ?string $navigationLabel = 'Sesi pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Keamanan';

    protected static ?int $navigationSort = 20;

    public static function getPages(): array
    {
        return [
            'index' => ListUserSessions::route('/'),
        ];
    }
}
