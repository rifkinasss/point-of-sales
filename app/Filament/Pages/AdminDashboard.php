<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

class AdminDashboard extends Dashboard
{
    protected static ?string $slug = 'dashboard';

    public function getColumns(): int|array
    {
        return ['lg' => 6, 'md' => 3, 'default' => 1];
    }
}
