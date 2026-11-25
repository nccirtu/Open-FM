<?php

namespace App\Providers\Filament;

use App\Filament\Actions\RegisterOrganizationAction;
use App\Filament\Integrations\MapWidget\WasteContainerMapWidget;
use App\Filament\PanelSwitcherMenuItems;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPermissionsTeamFromTenant;
use App\Models\Organization;
use App\Services\AppSettings;
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

class WastePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('waste')
            ->strictAuthorization()
            ->path('waste')
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
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('waste.navigation.operations')),
                NavigationGroup::make()->label(fn (): string => __('waste.navigation.inventory')),
                NavigationGroup::make()->label(fn (): string => __('waste.navigation.analysis')),
                NavigationGroup::make()->label(fn (): string => __('waste.navigation.compliance')),
            ])
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->hasRole('Admin') ?? false),
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->navigationGroup(fn (): string => __('waste.navigation.compliance'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                ...(Plugins::installed('qr-code') ? [QrCodePlugin::make()->scanner()->navigationGroup(fn (): string => __('waste.navigation.operations'))->navigationSort(90)->enabledWhen(fn (): bool => Plugins::active('qr-code'))] : []),
            ])
            ->discoverResources(in: app_path('Filament/Waste/Resources'), for: 'App\\Filament\\Waste\\Resources')
            ->discoverPages(in: app_path('Filament/Waste/Pages'), for: 'App\\Filament\\Waste\\Pages')
            ->discoverClusters(in: app_path('Filament/Waste/Clusters'), for: 'App\\Filament\\Waste\\Clusters')
            ->discoverWidgets(in: app_path('Filament/Waste/Widgets'), for: 'App\\Filament\\Waste\\Widgets')
            ->widgets([
                ...(Plugins::installed('map-widget') ? [WasteContainerMapWidget::class] : []),
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
