<?php

namespace App\Providers\Filament;

use App\Filament\Actions\RegisterOrganizationAction;
use App\Filament\PanelSwitcherMenuItems;
use App\Filament\Pm\Pages\CostCentersPage;
use App\Filament\Pm\Pages\PmDashboard;
use App\Filament\Pm\Widgets\MyTasksWidget;
use App\Filament\Pm\Widgets\ProjectBudgetChartWidget;
use App\Filament\Pm\Widgets\ProjectHealthChartWidget;
use App\Filament\Pm\Widgets\ProjectPortfolioWidget;
use App\Filament\Pm\Widgets\ProjectStatsWidget;
use App\Filament\Pm\Widgets\TasksByStatusChartWidget;
use App\Filament\Pm\Widgets\UpcomingMilestonesWidget;
use App\Filament\Resources\ApiTokenResource;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use App\Models\Organization;
use App\Services\AppSettings;
use App\Support\Plugins\PluginNavigation;
use App\Support\Plugins\Plugins;
use Cafm\FormBuilder\FormBuilderPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
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
use Slimani\MediaManager\MediaManagerPlugin;

class PmPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('pm')
            ->strictAuthorization()
            ->profile()
            ->path('pm')
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
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('projects.navigation_group')),
                NavigationGroup::make()->label(fn (): string => __('app.nav_groups.objects')),
                NavigationGroup::make()->label(fn (): string => __('form-builder.navigation.group')),
            ])
            ->topNavigation()
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->navigationGroup(fn (): string => __('form-builder.navigation.group'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                MediaManagerPlugin::make()
                    ->tenantAware()
                    ->navigationGroup(fn (): string => __('app.nav_groups.objects'))
                    ->navigationSort(30)
                    ->navigationLabel(fn (): string => __('documents.media.navigation'))
                    ->navigationIcon('heroicon-o-folder'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationItems([
                PluginNavigation::placeholder('form-builder', fn (): string => __('plugins.features.forms'), fn (): string => __('form-builder.navigation.group')),
            ])
            ->pages([
                PmDashboard::class,
                CostCentersPage::class,
            ])
            ->brandLogo(fn (): string => AppSettings::brandLogo('light'))
            ->darkModeBrandLogo(fn (): string => AppSettings::brandLogo('dark'))
            ->brandLogoHeight(fn (): string => AppSettings::logoHeight())
            ->favicon(fn (): string => AppSettings::brandFavicon())
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => __('app.api_tokens.title'))
                    ->icon('heroicon-o-key')
                    ->url(fn (): string => ApiTokenResource::getUrl('index')),
                MenuItem::make()
                    ->label(fn (): string => __('app.api_docs'))
                    ->icon('heroicon-o-book-open')
                    ->url('/docs/api', shouldOpenInNewTab: true),
                ...PanelSwitcherMenuItems::make(),
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                ProjectStatsWidget::class,
                ProjectPortfolioWidget::class,
                MyTasksWidget::class,
                UpcomingMilestonesWidget::class,
                TasksByStatusChartWidget::class,
                ProjectHealthChartWidget::class,
                ProjectBudgetChartWidget::class,
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
