<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\AdminDashboard;
use App\Filament\Admin\Pages\ManageAppSettings;
use App\Filament\Admin\Pages\ManagePlugins;
use App\Filament\Admin\Resources\WorkflowResource;
use App\Filament\Admin\Widgets\OrganizationsOverviewWidget;
use App\Filament\Admin\Widgets\PluginStatusWidget;
use App\Filament\Admin\Widgets\SystemHealthWidget;
use App\Filament\PanelSwitcherMenuItems;
use App\Flow\Actions\CreateMaintenanceOrderAction;
use App\Flow\Actions\CreateTicketAction;
use App\Flow\Conditions\SlaBreachedCondition;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\ScopeAdminPanelToAdministeredOrganizations;
use App\Http\Middleware\SetLocale;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\MaintenanceOrder;
use App\Models\Project;
use App\Models\Property;
use App\Models\Task;
use App\Models\Ticket;
use App\Services\AppSettings;
use App\Support\Plugins\PluginNavigation;
use App\Support\Plugins\Plugins;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Cafm\CustomFields\CustomFieldsPlugin;
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
use Packstub\Flow\FlowPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->strictAuthorization()
            ->path('admin')
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->brandName('Administration')
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
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->plugins([
                FilamentWidgetGridPlugin::make()
                    ->columns(config('filament-widget-grid.columns'))
                    ->cellHeight(config('filament-widget-grid.cell_height'))
                    ->maxHeight(config('filament-widget-grid.max_height'))
                    ->canViewWidget(fn (string $widget): bool => class_exists($widget) && $widget::canView())
                    ->canCustomize(fn (): bool => auth()->check())
                    ->canManageDefaults(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ...(Plugins::installed('form-builder') ? [FormBuilderPlugin::make()->manageForms()->designerNavigationGroup(fn (): string => __('app.admin_groups.forms'))->enabledWhen(fn (): bool => Plugins::active('form-builder'))] : []),
                ...(Plugins::installed('custom-fields') ? [CustomFieldsPlugin::make()->manageFields()->navigationGroup(fn (): string => __('app.admin_groups.forms'))->enabledWhen(fn (): bool => Plugins::active('custom-fields'))] : []),
                FilamentShieldPlugin::make()->centralApp(),
                ...(Plugins::installed('qr-code') ? [QrCodePlugin::make()->manageFormats()->navigationGroup(fn (): string => __('app.admin_groups.settings'))->navigationSort(40)->enabledWhen(fn (): bool => Plugins::active('qr-code'))] : []),
                FlowPlugin::make()
                    ->authorize(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                    ->navigationGroup(__('app.admin_groups.automation'))
                    ->resource(WorkflowResource::class)
                    ->actions([
                        CreateTicketAction::class,
                        CreateMaintenanceOrderAction::class,
                    ])
                    ->conditions([
                        SlaBreachedCondition::class,
                    ])
                    ->models([
                        Ticket::class,
                        Task::class,
                        Project::class,
                        Contract::class,
                        MaintenanceOrder::class,
                        Asset::class,
                        Property::class,
                    ])
                    ->resolveTenantUsing(fn (array $payload) => $payload['model']?->organization ?? null),
            ])
            ->pages([
                AdminDashboard::class,
                ManageAppSettings::class,
                ManagePlugins::class,
            ])
            ->widgets([
                SystemHealthWidget::class,
                OrganizationsOverviewWidget::class,
                PluginStatusWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('api-docs')
                    ->label(fn (): string => __('app.api_docs'))
                    ->icon('heroicon-o-book-open')
                    ->group(fn (): string => __('app.admin_groups.settings'))
                    ->sort(90)
                    ->url('/docs/api', shouldOpenInNewTab: true),
                PluginNavigation::placeholder('form-builder', fn (): string => __('plugins.features.form_designer'), fn (): string => __('app.admin_groups.forms')),
                PluginNavigation::placeholder('custom-fields', fn (): string => __('plugins.features.custom_fields'), fn (): string => __('app.admin_groups.forms')),
                PluginNavigation::placeholder('qr-code', fn (): string => __('plugins.features.label_formats'), fn (): string => __('app.admin_groups.settings'), 40),
            ])
            ->userMenuItems([
                ...PanelSwitcherMenuItems::make(),
            ])
            ->topNavigation()
            ->navigationGroups([
                NavigationGroup::make()->label(fn (): string => __('app.admin_groups.management')),
                NavigationGroup::make()->label(fn (): string => __('app.admin_groups.tickets')),
                NavigationGroup::make()->label(fn (): string => __('app.admin_groups.automation')),
                NavigationGroup::make()->label(fn (): string => __('app.admin_groups.forms')),
                NavigationGroup::make()->label(fn (): string => __('app.admin_groups.settings')),
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
                SetLocale::class,
            ])
            ->authMiddleware([
                ScopeAdminPanelToAdministeredOrganizations::class,
            ], isPersistent: true);
    }
}
