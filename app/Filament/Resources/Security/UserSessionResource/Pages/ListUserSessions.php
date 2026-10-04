<?php

namespace App\Filament\Resources\Security\UserSessionResource\Pages;

use App\Filament\Resources\Security\UserSessionResource;
use Smony\FilamentUserSessions\Resources\SessionResource\Pages\ListSessions;

class ListUserSessions extends ListSessions
{
    protected static string $resource = UserSessionResource::class;
}
