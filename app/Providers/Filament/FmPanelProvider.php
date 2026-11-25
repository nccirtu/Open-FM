<?php

namespace App\Providers\Filament;

use App\Filament\Actions\RegisterOrganizationAction;
use App\Filament\Contracts\Widgets\ExpiringContractsWidget;
use App\Filament\Fm\Pages\AssetCategoriesPage;
use App\Filament\Fm\Pages\FmDashboard;
use App\Filament\Fm\Resources\CompanyResource;
use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Filament\Fm\Resources\MaintenancePlanResource;
use App\Filament\Fm\Resources\MaintenancePlanTemplateResource;
use App\Filament\Fm\Resources\TicketResource;
use App\Filament\Fm\Widgets\AssetConditionChartWidget;
use App\Filament\Fm\Widgets\AssetsByServiceStatusChartWidget;
use App\Filament\Fm\Widgets\AssetsByTypeChartWidget;
use App\Filament\Fm\Widgets\CostsPerMonthChartWidget;
use App\Filament\Fm\Widgets\MaintenanceCompletedChartWidget;
use App\Filament\Fm\Widgets\MaintenanceStatsWidget;
use App\Filament\Fm\Widgets\MapPluginRequiredWidget;
use App\Filament\Fm\Widgets\OpenTicketsWidget;
use App\Filament\Fm\Widgets\TicketsByCategoryChartWidget;
use App\Filament\Fm\Widgets\TicketsByPriorityChartWidget;
use App\Filament\Fm\Widgets\TicketsPerPropertyChartWidget;
use App\Filament\Fm\Widgets\TicketStatsWidget;
use App\Filament\Fm\Widgets\TicketTrendChartWidget;
use App\Filament\Fm\Widgets\UpcomingMaintenanceWidget;
use App\Filament\Integrations\MapWidget\PropertyMapWidget;
use App\Filament\PanelSwitcherMenuItems;
use App\Filament\Pm\Widgets\MyTasksWidget;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\PropertyResource;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use App\Models\Organization;
use App\Services\AppSettings;
use App\Support\Plugins\PluginNavigation;
use App\Support\Plugins\Plugins;
use Cafm\FormBuilder\FormBuilderPlugin;
use Cafm\QrCode\QrCodePlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
use LaBoiteACode\FilamentActivityTimeline\FilamentActivityTimelinePlugin;
use Slimani\MediaManager\MediaManagerPlugin;

class FmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('fm')
            ->strictAuthorization()
            ->path('fm')
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
            ->brandName('Gebäudemanagement')
            ->brandLogo(fn (): string => AppSettings::brandLogo('light'))
            ->darkModeBrandLogo(fn (): string => AppSettings::brandLogo('dark'))
            ->brandLogoHeight(fn (): string => AppSettings::logoHeight())
            ->favicon(fn (): string => AppSettings::brandFavicon())
            ->viteTheme('resources/css/filament/fm/theme.css')
            ->colors([
                'primary' => Color::Neutral,
                'gray' => Color::Neutral,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->databaseNotifications()
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->navigationGroup(fn (): string => __('form-builder.navigation.group'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                FilamentActivityTimelinePlugin::make(),
                ...(Plugins::installed('qr-code') ? [QrCodePlugin::make()->scanner()->navigationGroup(fn (): string => __('app.nav_groups.objects'))->navigationSort(40)->enabledWhen(fn (): bool => Plugins::active('qr-code'))] : []),
                MediaManagerPlugin::make()
                    ->tenantAware()
                    ->navigationGroup(fn (): string => __('app.nav_groups.objects'))
                    ->navigationSort(30)
                    ->navigationLabel(fn (): string => __('documents.media.navigation'))
                    ->navigationIcon('heroicon-o-folder'),
            ])
            ->resources([
                TicketResource::class,
                MaintenancePlanResource::class,
                MaintenancePlanTemplateResource::class,
                MaintenanceOrderResource::class,
                PropertyResource::class,
                AssetResource::class,
                CompanyResource::class,
            ])
            ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('app.nav_groups.objects')),
                NavigationGroup::make()->label(fn (): string => __('app.nav_groups.tickets')),
                NavigationGroup::make()->label(fn (): string => __('maintenance.navigation_group')),
                NavigationGroup::make()->label(fn (): string => __('app.nav_groups.partners')),
                NavigationGroup::make()->label(fn (): string => __('form-builder.navigation.group')),
            ])
            ->navigationItems([
                PluginNavigation::placeholder('qr-code', fn (): string => __('plugins.features.qr_scanner'), fn (): string => __('app.nav_groups.objects'), 40),
                PluginNavigation::placeholder('form-builder', fn (): string => __('plugins.features.forms'), fn (): string => __('form-builder.navigation.group')),
            ])
            ->pages([
                FmDashboard::class,
                AssetCategoriesPage::class,
            ])
            ->widgets([
                ...(Plugins::installed('map-widget') ? [PropertyMapWidget::class] : []),
                MapPluginRequiredWidget::class,
                TicketStatsWidget::class,
                MaintenanceStatsWidget::class,
                OpenTicketsWidget::class,
                UpcomingMaintenanceWidget::class,
                TicketTrendChartWidget::class,
                AssetConditionChartWidget::class,
                AssetsByTypeChartWidget::class,
                AssetsByServiceStatusChartWidget::class,
                TicketsByCategoryChartWidget::class,
                TicketsByPriorityChartWidget::class,
                TicketsPerPropertyChartWidget::class,
                MaintenanceCompletedChartWidget::class,
                CostsPerMonthChartWidget::class,
                ExpiringContractsWidget::class,
                MyTasksWidget::class,
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
