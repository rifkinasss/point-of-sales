<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Security\UserSessionResource;
use App\Filament\Pages\AdminDashboard;
use App\Filament\Widgets\AdminQuickActionsWidget;
use App\Filament\Widgets\PlatformDistributionWidget;
use App\Filament\Widgets\PlatformOverviewWidget;
use App\Filament\Widgets\RecentAdminActivityWidget;
use App\Filament\Widgets\SecurityOverviewWidget;
use App\Filament\Widgets\StorePerformanceWidget;
use App\Http\Middleware\AuthenticateFilamentPanel;
use App\Services\PostLoginRedirector;
use Asharif88\FilamentPlotly\FilamentPlotlyPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->brandName('NasLabs ERP Admin')
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
                    ->redirectTo(fn (\App\Models\User $user): string => app(PostLoginRedirector::class)->pathFor($user))
                    ->logSwitches()
                    ->banner(position: 'top', style: 'dark'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                UserSessionResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Overview'),
                NavigationGroup::make('Platform'),
                NavigationGroup::make('Operations'),
                NavigationGroup::make('Security & Audit'),
                NavigationGroup::make('System'),
            ])
            ->widgets([
                PlatformOverviewWidget::class,
                SecurityOverviewWidget::class,
                PlatformDistributionWidget::class,
                RecentAdminActivityWidget::class,
                StorePerformanceWidget::class,
                AdminQuickActionsWidget::class,
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
