<?php

namespace App\Providers\Filament;

use App\Http\Middleware\AuthenticateFilamentPanel;
use Asharif88\FilamentPlotly\FilamentPlotlyPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Packstub\AccountSwitcher\AccountSwitcherPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;
use VitisStudio\FilamentHoverSidebar\HoverSidebarPlugin;
use Xplodman\CountUp\CountUpPlugin;

class OwnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('owner')
            ->path('owner')
            ->viteTheme('resources/css/filament/theme.css')
            ->brandName('NasLabs ERP — Owner')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarWidth('17rem')
            ->collapsedSidebarWidth('4.5rem')
            ->plugin(
                HoverSidebarPlugin::make()
                    ->openDelay(90)
                    ->closeDelay(180),
            )
            ->plugin(AppVersionPlugin::make()->badge()->neutral())
            ->plugin(FilamentPlotlyPlugin::make())
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->navigationGroups([
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Operasional'),
                NavigationGroup::make('Pembelian'),
                NavigationGroup::make('Inventori'),
                NavigationGroup::make('Katalog'),
                NavigationGroup::make('Keuangan'),
                NavigationGroup::make('Pengaturan'),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateFilamentPanel::class,
            ]);
    }
}
