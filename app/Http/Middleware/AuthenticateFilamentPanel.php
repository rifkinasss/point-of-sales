<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class AuthenticateFilamentPanel extends Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('auth.login');
    }
}
