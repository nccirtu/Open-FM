<?php

use App\Enums\PluginStatus;
use App\Enums\TicketStatus;
use App\Filament\Admin\Pages\AdminDashboard;
use App\Filament\Admin\Widgets\OrganizationsOverviewWidget;
use App\Filament\Admin\Widgets\PluginStatusWidget;
use App\Filament\Admin\Widgets\SystemHealthWidget;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Plugin;
use App\Models\Property;
use App\Models\Ticket;
use App\Support\Plugins\Plugins;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

function callWidgetMethod(object $widget, string $method): mixed
{
    return Closure::bind(fn (): mixed => $this->{$method}(), $widget, $widget::class)();
}

it('renders the admin dashboard with all widgets for super admins', function () {
    loginAsSuperAdminInAdminPanel();

    Livewire::test(AdminDashboard::class)
        ->assertSuccessful()
        ->assertSeeLivewire(SystemHealthWidget::class)
        ->assertSeeLivewire(OrganizationsOverviewWidget::class)
        ->assertSeeLivewire(PluginStatusWidget::class);
});

it('runs the admin dashboard grid with twelve columns', function () {
    expect(Filament::getPanel('admin')->getPlugin('filament-widget-grid')->getColumns())->toBe(12);
});

it('packs the default admin dashboard into full rows', function () {
    loginAsSuperAdminInAdminPanel();

    $rows = collect(Livewire::test(AdminDashboard::class)->get('widgetGridLayout'))->groupBy('y');

    expect($rows)->toHaveCount(2);

    $rows->each(fn (Collection $row) => expect($row->sum('w'))->toBe(
        12,
        $row->pluck('widget')->map(fn (string $widget): string => class_basename($widget))->implode(', '),
    ));
});

it('shows installation-wide widgets only to super admins', function () {
    loginAsAdminInAdminPanel();

    expect(SystemHealthWidget::canView())->toBeFalse()
        ->and(PluginStatusWidget::canView())->toBeFalse()
        ->and(OrganizationsOverviewWidget::canView())->toBeTrue();

    $widgets = collect(Livewire::test(AdminDashboard::class)->get('widgetGridLayout'))->pluck('widget')->all();

    expect($widgets)->toBe([OrganizationsOverviewWidget::class]);

    auth()->user()->forceFill(['is_super_admin' => true])->save();

    expect(SystemHealthWidget::canView())->toBeTrue()
        ->and(PluginStatusWidget::canView())->toBeTrue();
});

it('counts failed and pending jobs in the system health widget', function () {
    loginAsSuperAdminInAdminPanel();

    $widget = new SystemHealthWidget;

    expect(callWidgetMethod($widget, 'failedJobs'))->toBe(0)
        ->and(callWidgetMethod($widget, 'pendingJobs'))->toBeNull();

    Livewire::test(SystemHealthWidget::class)
        ->assertSee(__('admin_dashboard.system.failed_jobs_none'))
        ->assertDontSee(__('admin_dashboard.system.failed_jobs_some'));

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Boom',
        'failed_at' => now(),
    ]);

    config(['queue.default' => 'database']);

    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    expect(callWidgetMethod($widget, 'failedJobs'))->toBe(1)
        ->and(callWidgetMethod($widget, 'pendingJobs'))->toBe(1);

    Livewire::test(SystemHealthWidget::class)
        ->assertSee(__('admin_dashboard.system.failed_jobs_some'))
        ->assertSee(__('admin_dashboard.system.queue_connection', ['connection' => 'database']));
});

it('warns when debug mode is on in production', function () {
    loginAsSuperAdminInAdminPanel();

    Livewire::test(SystemHealthWidget::class)
        ->assertDontSee(__('admin_dashboard.system.debug_warning'));

    app()->detectEnvironment(fn (): string => 'production');
    config(['app.debug' => true]);

    Livewire::test(SystemHealthWidget::class)
        ->assertSee(__('admin_dashboard.system.debug_warning'));
});

it('lists plugins with their status in the plugin status widget', function () {
    loginAsSuperAdminInAdminPanel();

    Plugin::factory()->create(['slug' => 'fremd-plugin', 'name' => 'Fremd Plugin', 'status' => PluginStatus::Failed]);
    Plugin::factory()->disabled()->create(['slug' => 'zweites-plugin', 'name' => 'Zweites Plugin']);

    Livewire::test(PluginStatusWidget::class)
        ->assertSuccessful()
        ->assertSee('Fremd Plugin')
        ->assertSee(__('plugins.status.failed'))
        ->assertSee('Zweites Plugin')
        ->assertSee(__('plugins.admin.states.not_installed'));
});

it('highlights a registry version that differs from the installed package', function () {
    requiresPlugin('qr-code');

    loginAsSuperAdminInAdminPanel();

    Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'name' => 'QR Code', 'version' => '0.0.1']);

    Livewire::test(PluginStatusWidget::class)
        ->assertSee(__('admin_dashboard.plugins.registry_version', ['version' => '0.0.1']));
});

it('points to plugins:restore when registered plugins are missing on disk', function () {
    loginAsSuperAdminInAdminPanel();

    Plugin::factory()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code', 'name' => 'QR Code']);
    Plugins::simulateMissing('qr-code');

    Livewire::test(PluginStatusWidget::class)
        ->assertSee('plugins:restore');
});

it('summarises each organisation in the organisations overview', function () {
    loginAsSuperAdminInAdminPanel();

    $default = defaultOrganization();
    $other = Organization::factory()->create(['name' => 'Zweite GmbH', 'slug' => 'zweite']);

    inOrganization($other, function (): void {
        $properties = Property::factory()->count(2)->create();
        Asset::factory()->count(3)->create(['property_id' => $properties->first()->getKey()]);
        Ticket::factory()->count(2)->create(['property_id' => $properties->first()->getKey(), 'status' => TicketStatus::Reported]);
        Ticket::factory()->create(['property_id' => $properties->first()->getKey(), 'status' => TicketStatus::Closed]);
    });

    Livewire::test(OrganizationsOverviewWidget::class)
        ->assertCanSeeTableRecords([$default, $other])
        ->assertTableColumnStateSet('properties_count', 2, $other)
        ->assertTableColumnStateSet('assets_count', 3, $other)
        ->assertTableColumnStateSet('open_tickets_count', 2, $other)
        ->assertTableColumnStateSet('properties_count', 0, $default)
        ->assertTableColumnStateSet('open_tickets_count', 0, $default);
});

it('limits the organisations overview to the own organisations of non-super admins', function () {
    loginAsAdminInAdminPanel();

    $default = defaultOrganization();
    $other = Organization::factory()->create(['name' => 'Fremde GmbH', 'slug' => 'fremde']);

    Livewire::test(OrganizationsOverviewWidget::class)
        ->assertCanSeeTableRecords([$default])
        ->assertCanNotSeeTableRecords([$other]);
});

it('renders the dashboard grid gate in every panel', function () {
    loginAsSuperAdminInAdminPanel();

    $organization = defaultOrganization();

    foreach (['admin', 'fm', 'pm', 'contracts', 'energy', 'waste', 'portal'] as $panel) {
        $url = Filament::getPanel($panel)->hasTenancy()
            ? Filament::getPanel($panel)->getUrl($organization)
            : Filament::getPanel($panel)->getUrl();

        $this->get($url)
            ->assertOk()
            ->assertSee("setAttribute('data-fi-wg-ready'", false);
    }
});

it('keeps the grid gate script and the skeleton styles in sync', function () {
    $css = file_get_contents(resource_path('css/filament/fm/theme.css'));
    $script = file_get_contents(resource_path('views/filament/widget-grid-gate.blade.php'));

    expect($css)->toContain('html:not([data-fi-wg-ready]) .fi-wg-grid')
        ->and($script)->toContain('data-fi-wg-ready');
});
