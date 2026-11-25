<?php

use App\Filament\Admin\Pages\ManagePlugins;
use App\Filament\Fm\Pages\FmDashboard;
use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Fm\Widgets\MapPluginRequiredWidget;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\ProjectResource\Pages\ProjectGantt;
use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Filament\Resources\PropertyResource\Pages\StructurePage;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Plugin;
use App\Models\Project;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Classification\ClassificationTemplateImporter;
use App\Services\Gdpr\PersonalDataExporter;
use App\Support\Plugins\CustomFieldsBridge;
use App\Support\Plugins\Plugins;
use Cafm\FormBuilder\Filament\Pages\FillFormPage;
use Cafm\FormBuilder\Models\Form;
use Cafm\QrCode\Filament\Pages\ScanQrCodePage;
use Cafm\QrCode\QrCode;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

function fakePluginCatalog(): void
{
    config([
        'plugins.catalog.demo-plugin' => ['package' => 'laravel/framework', 'category' => 'structure', 'icon' => 'heroicon-o-cube'],
        'plugins.catalog.demo-child' => ['package' => 'livewire/livewire', 'category' => 'structure', 'icon' => 'heroicon-o-cube', 'requires' => ['demo-plugin']],
        'plugins.catalog.demo-missing' => ['package' => 'vendor/does-not-exist', 'category' => 'structure', 'icon' => 'heroicon-o-cube'],
    ]);
}

it('treats installed packages as enabled for all organizations and missing ones as unavailable', function () {
    fakePluginCatalog();

    expect(Plugins::installed('demo-plugin'))->toBeTrue()
        ->and(Plugins::enabled('demo-plugin'))->toBeTrue()
        ->and(Plugins::availableFor('demo-plugin', Organization::factory()->create()))->toBeTrue()
        ->and(Plugins::installed('demo-missing'))->toBeFalse()
        ->and(Plugins::active('demo-missing'))->toBeFalse()
        ->and(Plugins::unavailableReason('demo-missing'))->toBe(__('plugins.required.not_installed', ['plugin' => 'Demo Missing']));
});

it('disables a plugin together with the plugins that depend on it', function () {
    fakePluginCatalog();

    Plugin::factory()->disabled()->create(['slug' => 'demo-plugin', 'package' => 'laravel/framework']);

    expect(Plugins::enabled('demo-plugin'))->toBeFalse()
        ->and(Plugins::enabled('demo-child'))->toBeFalse()
        ->and(Plugins::unavailableReason('demo-plugin'))->toBe(__('plugins.required.disabled', ['plugin' => 'Demo Plugin']));
});

it('limits a plugin to the assigned organizations', function () {
    fakePluginCatalog();
    $allowed = Organization::factory()->create();
    $other = Organization::factory()->create();

    $plugin = Plugin::factory()->create(['slug' => 'demo-plugin', 'package' => 'laravel/framework']);
    $plugin->organizations()->attach($allowed);
    Plugins::flush();

    expect(Plugins::availableFor('demo-plugin', $allowed))->toBeTrue()
        ->and(Plugins::availableFor('demo-plugin', $other))->toBeFalse()
        ->and(Plugins::availableFor('demo-plugin', null))->toBeTrue();
});

it('simulates missing plugins for tests', function () {
    fakePluginCatalog();
    Plugins::simulateMissing('demo-plugin');

    expect(Plugins::installed('demo-plugin'))->toBeFalse()
        ->and(Plugins::enabled('demo-child'))->toBeFalse();
});

it('grants and revokes the super admin flag via the console', function () {
    $user = User::factory()->create(['email' => 'op@example.com']);

    $this->artisan('app:super-admin', ['email' => 'op@example.com'])->assertSuccessful();
    expect($user->fresh()->isSuperAdmin())->toBeTrue()
        ->and($user->fresh()->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->artisan('app:super-admin', ['email' => 'op@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->isSuperAdmin())->toBeFalse();

    $this->artisan('app:super-admin', ['email' => 'unbekannt@example.com'])->assertFailed();
});

it('lets only super admins manage plugins', function () {
    loginAsAdminInAdminPanel();

    expect(ManagePlugins::canAccess())->toBeFalse();

    auth()->user()->forceFill(['is_super_admin' => true])->save();

    expect(ManagePlugins::canAccess())->toBeTrue();
});

it('lists the catalog, toggles plugins and assigns organizations in the plugin management', function () {
    fakePluginCatalog();
    loginAsSuperAdmin();
    Filament::setTenant(null);
    Filament::setCurrentPanel('admin');
    $organization = Organization::factory()->create();

    Livewire::test(ManagePlugins::class)
        ->assertCanSeeTableRecords(['demo-plugin', 'demo-missing'])
        ->assertTableActionVisible('disable', 'demo-plugin')
        ->assertTableActionHidden('organizations', 'demo-missing')
        ->callTableAction('disable', 'demo-plugin')
        ->assertTableActionVisible('enable', 'demo-plugin');

    expect(Plugins::enabled('demo-plugin'))->toBeFalse();

    Livewire::test(ManagePlugins::class)
        ->callTableAction('enable', 'demo-plugin')
        ->callTableAction('organizations', 'demo-plugin', data: ['organizations' => [$organization->getKey()]]);

    expect(Plugins::enabled('demo-plugin'))->toBeTrue()
        ->and(Plugins::availableFor('demo-plugin', $organization))->toBeTrue()
        ->and(Plugins::availableFor('demo-plugin', defaultOrganization()))->toBeFalse();
});

it('replaces the QR actions with a plugin hint when QR Code is missing', function () {
    loginAsAdminInFmPanel();
    Plugins::simulateMissing('qr-code');

    $asset = Asset::factory()->create();

    Livewire::test(ViewAsset::class, ['record' => $asset->getKey()])
        ->assertActionVisible('qrCode')
        ->mountAction('qrCode')
        ->assertMountedActionModalSee(__('plugins.required.heading', ['plugin' => 'QR Code']))
        ->assertMountedActionModalSee(__('plugins.required.contact_admin'));
});

it('offers super admins the way to the plugin management in hints', function () {
    loginAsAdminInFmPanel();
    auth()->user()->forceFill(['is_super_admin' => true])->save();
    Plugins::simulateMissing('structure-tree');

    $property = Property::factory()->create();

    Livewire::test(StructurePage::class, ['record' => $property->getKey()])
        ->assertSee(__('plugins.required.heading', ['plugin' => 'Structure Tree']))
        ->assertSee(__('plugins.required.manage'))
        ->assertSee(ManagePlugins::getUrl(['plugin' => 'structure-tree'], panel: 'admin'), escape: false);
});

it('shows plugin hints on the gantt and BIM pages and hides the IFC upload', function () {
    loginAsAdmin();
    Plugins::simulateMissing('gantt-chart', 'bim-viewer');

    Livewire::test(ProjectGantt::class, ['record' => Project::factory()->create()->getKey()])
        ->assertSee(__('plugins.required.heading', ['plugin' => 'Gantt Chart']));

    Livewire::test(BimPage::class, ['record' => Property::factory()->create()->getKey()])
        ->assertSee(__('plugins.required.heading', ['plugin' => 'BIM Viewer']))
        ->assertActionHidden('upload');
});

it('drops custom fields when the plugin is missing', function () {
    loginAsAdmin();
    Plugins::simulateMissing('custom-fields');

    expect(CustomFieldsBridge::formComponents('asset'))->toBe([])
        ->and(CustomFieldsBridge::infolistComponents('asset'))->toBe([])
        ->and(CustomFieldsBridge::tableColumns('asset'))->toBe([])
        ->and(CustomFieldsBridge::exportColumns('asset'))->toBe([]);
});

it('replaces receipt capture with a hint but keeps plain cost entries working', function () {
    loginAsAdminInFmPanel();
    Plugins::simulateMissing('receipt-capture');

    $ticket = Ticket::factory()->create();

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewTicket::class])
        ->assertActionVisible(TestAction::make('captureReceipt')->table())
        ->assertActionVisible(TestAction::make('create')->table())
        ->mountAction(TestAction::make('captureReceipt')->table())
        ->assertMountedActionModalSee(__('plugins.required.heading', ['plugin' => 'Receipt Capture']));
});

it('shows the map hint on the dashboard only to super admins', function () {
    loginAsAdminInFmPanel();
    Plugins::simulateMissing('map-widget');

    $widgets = fn (): array => collect(Livewire::test(FmDashboard::class)->get('widgetGridLayout'))->pluck('widget')->all();

    expect($widgets())->not->toContain(MapPluginRequiredWidget::class);

    auth()->user()->forceFill(['is_super_admin' => true])->save();

    expect($widgets())->toContain(MapPluginRequiredWidget::class);
});

it('skips custom field sections of classification templates without the plugin', function () {
    loginAsAdmin();
    Plugins::simulateMissing('custom-fields');

    expect(fn () => app(ClassificationTemplateImporter::class)->importCustomFieldSections([
        ['entity' => 'asset', 'key' => 'demo', 'label' => 'Demo', 'fields' => []],
    ]))->not->toThrow(Throwable::class);

    if (Schema::hasTable('custom_fields_sections')) {
        expect(DB::table('custom_fields_sections')->count())->toBe(0);
    }
});

it('refuses form template imports and skips form submissions in GDPR exports without the form builder', function () {
    $user = loginAsAdmin();
    Plugins::simulateMissing('form-builder');

    $this->artisan('forms:import-template', ['file' => 'begehung.form.json'])->assertFailed();

    expect(collect(app(PersonalDataExporter::class)->export($user))->pluck('label'))->not->toContain('Formular-Eingaben');
});

it('hides the pages of a disabled QR plugin and refuses its codes', function () {
    requiresPlugin('qr-code');
    loginAsAdminInFmPanel();
    $asset = Asset::factory()->create();

    Plugin::factory()->disabled()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code']);

    expect(ScanQrCodePage::canAccess())->toBeFalse();

    $this->get(QrCode::url($asset))->assertNotFound();
});

it('hides forms of a plugin that is not enabled for the current organization', function () {
    requiresPlugin('form-builder');
    loginAsAdminInFmPanel();

    $form = Form::query()->create(['name' => 'Begehung', 'slug' => 'begehung', 'panels' => ['fm'], 'schema' => []]);
    $form->publish();

    $plugin = Plugin::factory()->create(['slug' => 'form-builder', 'package' => 'cafm/form-builder']);
    $plugin->organizations()->attach(Organization::factory()->create());
    Plugins::flush();

    expect(Plugins::active('form-builder'))->toBeFalse();

    $this->get(FillFormPage::getUrl(['form' => $form->slug], panel: 'fm', tenant: defaultOrganization()))->assertNotFound();
});

it('shows navigation placeholders for missing plugin features to super admins', function () {
    requiresPlugin('qr-code');
    loginAsAdminInFmPanel();
    auth()->user()->forceFill(['is_super_admin' => true])->save();

    Plugin::factory()->disabled()->create(['slug' => 'qr-code', 'package' => 'cafm/qr-code']);

    $this->get(FmDashboard::getUrl(panel: 'fm', tenant: defaultOrganization()))
        ->assertOk()
        ->assertSee(ManagePlugins::getUrl(['plugin' => 'qr-code'], panel: 'admin'), escape: false);
});
