<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateFilamentPanel;
use App\Http\Middleware\EnsureCashierShiftWorkflow;
use App\Filament\Cashier\Widgets\CashierShiftOverview;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Packstub\AccountSwitcher\AccountSwitcherPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;
use Xplodman\CountUp\CountUpPlugin;

class CashierPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('cashier')
            ->path('cashier')
            ->viteTheme('resources/css/filament/theme.css')
            ->brandName('NasLabs ERP — Kasir')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->navigation(false)
            ->plugin(AppVersionPlugin::make()->badge()->neutral())
            ->plugin(
                FilamentLanguageSwitcherPlugin::make()
                    ->locales(['id', 'en'])
                    ->rememberLocale(days: 30),
            )
            ->plugin(
                CountUpPlugin::make()
                    ->duration(900)
                    ->thousandsSeparator('.')
                    ->decimalSeparator(','),
            )
            ->plugin(
                AccountSwitcherPlugin::make()
                    ->linkedAccounts(false)
                    ->logSwitches()
                    ->banner(position: 'top', style: 'dark'),
            )
            ->discoverResources(in: app_path('Filament/Cashier/Resources'), for: 'App\Filament\Cashier\Resources')
            ->discoverPages(in: app_path('Filament/Cashier/Pages'), for: 'App\Filament\Cashier\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Cashier/Widgets'), for: 'App\Filament\Cashier\Widgets')
            ->widgets([
                CashierShiftOverview::class,
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                EnsureCashierShiftWorkflow::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateFilamentPanel::class,
            ]);
    }
}
