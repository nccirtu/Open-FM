<?php

namespace App\Providers\Filament;

use App\Filament\Actions\RegisterOrganizationAction;
use App\Filament\Energy\Pages\EnergyDashboard;
use App\Filament\Energy\Pages\RecordReadingPage;
use App\Filament\Energy\Resources\EnergyGoalResource;
use App\Filament\Energy\Resources\MeterReadingResource;
use App\Filament\Energy\Resources\MeterResource;
use App\Filament\Energy\Widgets\CalibrationDueWidget;
use App\Filament\Energy\Widgets\ConsumptionByAssetChartWidget;
use App\Filament\Energy\Widgets\ConsumptionTrendChartWidget;
use App\Filament\Energy\Widgets\EnergyFlowWidget;
use App\Filament\Energy\Widgets\EnergyGoalsWidget;
use App\Filament\Energy\Widgets\EnergyStatsWidget;
use App\Filament\Energy\Widgets\PropertyEnergyComparisonWidget;
use App\Filament\Energy\Widgets\SuspiciousReadingsWidget;
use App\Filament\PanelSwitcherMenuItems;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use App\Models\Organization;
use App\Services\AppSettings;
use App\Support\Plugins\Plugins;
use Cafm\QrCode\QrCodePlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JohnRivera7\FilamentWidgetGrid\FilamentWidgetGridPlugin;

class EnergyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('energy')
            ->strictAuthorization()
            ->path('energy')
            ->viteTheme('resources/css/filament/fm/theme.css')
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantMenuItems([
                RegisterOrganizationAction::make(),
            ])
            ->tenantMiddleware([
                SetPermissionsTeamFromTenant::class,
                ApplyTenantScopes::class,
                SetLocale::class,
            ], isPersistent: true)
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->brandLogo(fn (): string => AppSettings::brandLogo('light'))
            ->darkModeBrandLogo(fn (): string => AppSettings::brandLogo('dark'))
            ->brandLogoHeight(fn (): string => AppSettings::logoHeight())
            ->favicon(fn (): string => AppSettings::brandFavicon())
            ->colors([
                'primary' => Color::Neutral,
                'gray' => Color::Neutral,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->databaseNotifications()
            ->topNavigation()
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ...(Plugins::installed('qr-code') ? [QrCodePlugin::make()->scanner()->navigationSort(40)->enabledWhen(fn (): bool => Plugins::active('qr-code'))] : []),
            ])
            ->resources([
                MeterResource::class,
                MeterReadingResource::class,
                EnergyGoalResource::class,
            ])
            ->pages([
                EnergyDashboard::class,
                RecordReadingPage::class,
            ])
            ->widgets([
                EnergyStatsWidget::class,
                ConsumptionTrendChartWidget::class,
                EnergyGoalsWidget::class,
                EnergyFlowWidget::class,
                ConsumptionByAssetChartWidget::class,
                PropertyEnergyComparisonWidget::class,
                SuspiciousReadingsWidget::class,
                CalibrationDueWidget::class,
            ])
            ->userMenuItems([
                ...PanelSwitcherMenuItems::make(),
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
                Authenticate::class,
                CheckOrganizationEmailDomain::class,
            ]);
    }
}
