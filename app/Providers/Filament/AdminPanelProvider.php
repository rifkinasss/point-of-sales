<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Security\UserSessionResource;
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
use Furic\FilamentRedeemCodes\FilamentRedeemCodesPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Islamv\FilamentSettingsPlugin\FilamentSettingsPlugin;
use Marcelodelgado\Announcements\AnnouncementsPlugin;
use Packstub\AccountSwitcher\AccountSwitcherPlugin;
use Smony\FilamentUserSessions\Widgets\OnlineSessionsWidget;
use SolutionForest\FilamentLoginGuard\FilamentLoginGuardPlugin;
use Vaslv\FilamentAppVersion\AppVersionPlugin;
use Xplodman\CountUp\CountUpPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/theme.css')
            ->brandName('NasLabs ERP — Admin')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->sidebarWidth('17rem')
            ->plugin(AppVersionPlugin::make()->badge()->neutral())
            ->plugin(
                CountUpPlugin::make()
                    ->duration(900)
                    ->thousandsSeparator('.')
                    ->decimalSeparator(','),
            )
            ->plugin(FilamentSettingsPlugin::make())
            ->plugin(FilamentLoginGuardPlugin::make())
            ->plugin(FilamentPlotlyPlugin::make())
            ->plugin(
                FilamentLanguageSwitcherPlugin::make()
                    ->locales(['id', 'en'])
                    ->rememberLocale(days: 30),
            )
            ->plugin(FilamentRedeemCodesPlugin::make())
            ->plugin(AnnouncementsPlugin::make()->pollingInterval('120s'))
            ->plugin(
                AccountSwitcherPlugin::make()
                    ->linkedAccounts(false)
                    ->logSwitches()
                    ->banner(position: 'top', style: 'dark'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                UserSessionResource::class,
            ])
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
                NavigationGroup::make('Promosi'),
                NavigationGroup::make('Keamanan'),
                NavigationGroup::make('Pengaturan'),
            ])
            ->widgets([
                OnlineSessionsWidget::class,
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
