<?php

namespace App\Providers\Filament;

use App\Filament\Actions\RegisterOrganizationAction;
use App\Filament\Contracts\Pages\ContractsDashboard;
use App\Filament\Contracts\Resources\ContractResource;
use App\Filament\Contracts\Widgets\ContractCostsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ContractsByTypeChartWidget;
use App\Filament\Contracts\Widgets\ContractStatsWidget;
use App\Filament\Contracts\Widgets\ExpiringContractsWidget;
use App\Filament\PanelSwitcherMenuItems;
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

class ContractsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('contracts')
            ->strictAuthorization()
            ->path('contracts')
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
            ->brandName('Vertragsmanagement')
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
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->navigationGroup(fn (): string => __('form-builder.navigation.group'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                MediaManagerPlugin::make()
                    ->tenantAware()
                    ->navigationGroup('')
                    ->navigationSort(20)
                    ->navigationLabel(fn (): string => __('documents.media.navigation'))
                    ->navigationIcon('heroicon-o-folder'),
            ])
            ->resources([
                ContractResource::class,
            ])
            ->navigationItems([
                PluginNavigation::placeholder('form-builder', fn (): string => __('plugins.features.forms'), fn (): string => __('form-builder.navigation.group')),
            ])
            ->pages([
                ContractsDashboard::class,
            ])
            ->widgets([
                ContractStatsWidget::class,
                ExpiringContractsWidget::class,
                ContractsByTypeChartWidget::class,
                ContractCostsByTypeChartWidget::class,
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
