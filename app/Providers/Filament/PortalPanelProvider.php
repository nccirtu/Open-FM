<?php

namespace App\Providers\Filament;

use App\Filament\PanelSwitcherMenuItems;
use App\Filament\Portal\Pages\PortalDashboard;
use App\Filament\Portal\Resources\AssetResource;
use App\Filament\Portal\Resources\TicketResource;
use App\Filament\Portal\Widgets\AwaitingConfirmationWidget;
use App\Filament\Portal\Widgets\MyPropertiesWidget;
use App\Filament\Portal\Widgets\MyReportsStatsWidget;
use App\Filament\Portal\Widgets\OpenReportsWidget;
use App\Filament\Portal\Widgets\ReportNewsWidget;
use App\Filament\Portal\Widgets\ReportsByCategoryChartWidget;
use App\Filament\Portal\Widgets\ReportsByStatusChartWidget;
use App\Filament\Portal\Widgets\ReportsTrendChartWidget;
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
use Filament\Navigation\NavigationItem;
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

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->strictAuthorization()
            ->path('portal')
            ->tenant(Organization::class, slugAttribute: 'slug')
            ->tenantMiddleware([
                SetPermissionsTeamFromTenant::class,
                ApplyTenantScopes::class,
                SetLocale::class,
            ], isPersistent: true)
            ->databaseNotifications()
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->brandName('Service-Portal')
            ->brandLogo(fn (): string => AppSettings::brandLogo('light'))
            ->darkModeBrandLogo(fn (): string => AppSettings::brandLogo('dark'))
            ->brandLogoHeight(fn (): string => AppSettings::logoHeight())
            ->favicon(fn (): string => AppSettings::brandFavicon())
            ->databaseNotifications()
            ->viteTheme('resources/css/filament/fm/theme.css')
            ->colors([
                'primary' => Color::Neutral,
                'gray' => Color::Neutral,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
                'success' => Color::Emerald,
                'info' => Color::Sky,
            ])
            ->resources([
                TicketResource::class,
                AssetResource::class,
            ])
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->navigationGroup(fn (): string => __('form-builder.navigation.group'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                ...(Plugins::installed('qr-code') ? [QrCodePlugin::make()->scanner()->navigationSort(90)->enabledWhen(fn (): bool => Plugins::active('qr-code'))] : []),
            ])
            ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('portal.navigation_group')),
                NavigationGroup::make()->label(fn (): string => __('app.nav_groups.objects')),
                NavigationGroup::make()->label(fn (): string => __('form-builder.navigation.group')),
            ])
            ->navigationItems([
                NavigationItem::make('report-ticket')
                    ->label(fn (): string => __('tickets.portal.report'))
                    ->icon('heroicon-o-plus-circle')
                    ->group(fn (): string => __('portal.navigation_group'))
                    ->sort(15)
                    ->url(fn (): string => TicketResource::getUrl('create'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.portal.resources.tickets.create')),
                PluginNavigation::placeholder('qr-code', fn (): string => __('plugins.features.qr_scanner'), null, 90),
                PluginNavigation::placeholder('form-builder', fn (): string => __('plugins.features.forms'), fn (): string => __('form-builder.navigation.group')),
            ])
            ->pages([
                PortalDashboard::class,
            ])
            ->widgets([
                MyReportsStatsWidget::class,
                OpenReportsWidget::class,
                ReportsByStatusChartWidget::class,
                AwaitingConfirmationWidget::class,
                ReportNewsWidget::class,
                MyPropertiesWidget::class,
                ReportsTrendChartWidget::class,
                ReportsByCategoryChartWidget::class,
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
