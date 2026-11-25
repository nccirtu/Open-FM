<?php

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Filament\Imports\WasteCollectionImporter;
use App\Filament\Imports\WasteContainerImporter;
use App\Filament\Waste\Clusters\Settings\Pages\WasteSettingsPage;
use App\Filament\Waste\Clusters\Settings\Resources\WasteContainerTypes\Pages\ManageWasteContainerTypes;
use App\Filament\Waste\Clusters\Settings\Resources\WasteEmissionFactors\Pages\ManageWasteEmissionFactors;
use App\Filament\Waste\Clusters\Settings\Resources\WasteFractionGroups\Pages\ManageWasteFractionGroups;
use App\Filament\Waste\Clusters\Settings\Resources\WasteFractions\Pages\ManageWasteFractions;
use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatmentCategories\Pages\ManageWasteTreatmentCategories;
use App\Filament\Waste\Clusters\Settings\Resources\WasteTreatments\Pages\ManageWasteTreatments;
use App\Filament\Waste\Pages\CollectionPointsPage;
use App\Filament\Waste\Pages\ConfirmPickupsPage;
use App\Filament\Waste\Pages\CostAllocationPage;
use App\Filament\Waste\Pages\HazardousRegisterPage;
use App\Filament\Waste\Pages\InvoiceCheckPage;
use App\Filament\Waste\Pages\PickupCalendarPage;
use App\Filament\Waste\Pages\RightsizingPage;
use App\Filament\Waste\Pages\WasteDashboard;
use App\Filament\Waste\Pages\WasteDocumentationPage;
use App\Filament\Waste\Pages\WasteEsgReportPage;
use App\Filament\Waste\Resources\WasteCollections\Pages\ListWasteCollections;
use App\Filament\Waste\Resources\WasteContainers\Pages\CreateWasteContainer;
use App\Filament\Waste\Resources\WasteContainers\Pages\EditWasteContainer;
use App\Filament\Waste\Resources\WasteContainers\Pages\ListWasteContainers;
use App\Filament\Waste\Resources\WasteEvidence\Pages\ManageWasteEvidence;
use App\Filament\Waste\Resources\WasteGoals\Pages\ManageWasteGoals;
use App\Filament\Waste\Resources\WastePickups\Pages\ListWastePickups;
use App\Filament\Waste\Resources\WasteSchedules\Pages\ManageWasteSchedules;
use App\Filament\Waste\Widgets\HazardousWasteWidget;
use App\Filament\Waste\Widgets\PickupCalendarWidget;
use App\Filament\Waste\Widgets\PickupExceptionsWidget;
use App\Filament\Waste\Widgets\WasteByFractionChartWidget;
use App\Filament\Waste\Widgets\WasteFlowWidget;
use App\Filament\Waste\Widgets\WasteGoalsWidget;
use App\Filament\Waste\Widgets\WasteStatsWidget;
use App\Models\Property;
use App\Models\Role;
use App\Models\StructureNode;
use App\Models\User;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Models\WasteSettings;
use App\Services\Waste\CollectionPoints;
use App\Services\Waste\WasteCollectionRecorder;
use Cafm\FormBuilder\Filament\Pages\FillFormPage;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * @return array{property: Property, node: StructureNode, paper: WasteContainer, second: WasteContainer, pickup: WastePickup}
 */
function wastePanelFixture(): array
{
    test()->travelTo(CarbonImmutable::parse('2026-10-08 10:00'));

    $property = Property::factory()->create(['property_number' => 'DEMO-01']);
    $node = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Müllplatz Nord']);
    $fraction = WasteFraction::factory()->paper()->create();
    $paper = WasteContainer::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $node->getKey(), 'waste_fraction_id' => $fraction->getKey(), 'number' => 'B-0101']);
    $second = WasteContainer::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $node->getKey(), 'waste_fraction_id' => $fraction->getKey(), 'number' => 'B-0103']);
    $pickup = WastePickup::factory()->forContainer($paper)->create(['due_on' => '2026-10-07']);

    return ['property' => $property, 'node' => $node, 'paper' => $paper, 'second' => $second, 'pickup' => $pickup];
}

it('renders every page of the waste panel', function (string $page) {
    loginAsAdminInWastePanel();
    wastePanelFixture();

    Livewire::test($page)->assertSuccessful();
})->with([
    'dashboard' => WasteDashboard::class,
    'calendar' => PickupCalendarPage::class,
    'confirmation page' => ConfirmPickupsPage::class,
    'containers' => ListWasteContainers::class,
    'new container' => CreateWasteContainer::class,
    'schedules' => ManageWasteSchedules::class,
    'pickups' => ListWastePickups::class,
    'collections' => ListWasteCollections::class,
    'general settings' => WasteSettingsPage::class,
    'fractions' => ManageWasteFractions::class,
    'fraction groups' => ManageWasteFractionGroups::class,
    'container types' => ManageWasteContainerTypes::class,
    'treatments' => ManageWasteTreatments::class,
    'treatment categories' => ManageWasteTreatmentCategories::class,
    'emission factors' => ManageWasteEmissionFactors::class,
    'goals' => ManageWasteGoals::class,
    'evidence' => ManageWasteEvidence::class,
    'documentation' => WasteDocumentationPage::class,
    'hazardous register' => HazardousRegisterPage::class,
    'invoice check' => InvoiceCheckPage::class,
    'esg report' => WasteEsgReportPage::class,
    'rightsizing' => RightsizingPage::class,
    'cost allocation' => CostAllocationPage::class,
]);

it('renders the dashboard widgets with recorded quantities', function (string $widget) {
    loginAsAdminInWastePanel();
    ['paper' => $paper] = wastePanelFixture();
    app(WasteCollectionRecorder::class)->recordForContainer($paper, CarbonImmutable::parse('2026-10-07 09:00'), WasteCollectionSource::Import, massKg: 52);

    Livewire::test($widget)->assertSuccessful();
})->with([
    'stats' => WasteStatsWidget::class,
    'chart' => WasteByFractionChartWidget::class,
    'flow' => WasteFlowWidget::class,
    'pickup exceptions' => PickupExceptionsWidget::class,
    'hazardous waste' => HazardousWasteWidget::class,
    'goals' => WasteGoalsWidget::class,
]);

it('opens the waste panel to waste, facility and service roles only', function () {
    loginAsAdmin();
    $panel = Filament::getPanel('waste');

    $user = function (string $role): User {
        $user = User::factory()->create();
        $user->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
        $user->assignRole($role);

        return $user;
    };

    expect($user('Abfall')->canAccessPanel($panel))->toBeTrue()
        ->and($user('Dienstleister')->canAccessPanel($panel))->toBeTrue()
        ->and($user('Melder')->canAccessPanel($panel))->toBeFalse()
        ->and($user('Verträge')->canAccessPanel($panel))->toBeFalse();
});

it('lets service providers confirm pickups but not manage containers', function () {
    loginAsAdmin();
    $provider = User::factory()->create();
    $provider->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
    $provider->assignRole('Dienstleister');

    expect($provider->can('confirm', WastePickup::class))->toBeTrue()
        ->and($provider->can('create', WasteContainer::class))->toBeFalse()
        ->and(Role::findByName('Abfall')->hasPermissionTo('create_waste::container'))->toBeTrue();
});

it('creates a schedule for a collection point valid until a date and generates its pickups', function () {
    loginAsAdminInWastePanel();
    ['property' => $property, 'node' => $node, 'paper' => $paper, 'second' => $second] = wastePanelFixture();

    Livewire::test(ManageWasteSchedules::class)
        ->callAction('create', data: [
            'property_id' => $property->getKey(),
            'structure_node_id' => $node->getKey(),
            'containers' => [$paper->getKey(), $second->getKey()],
            'source' => 'rule',
            'interval_unit' => 'week',
            'interval_value' => 1,
            'weekday' => 2,
            'starts_on' => '2026-10-08',
            'ends_on' => '2026-10-27',
            'confirmation_channels' => ['scan'],
        ])
        ->assertHasNoActionErrors();

    $schedule = WasteSchedule::query()->sole();

    expect($schedule->containers()->count())->toBe(2)
        ->and($schedule->ends_on?->toDateString())->toBe('2026-10-27')
        ->and($schedule->pickups()->orderBy('due_on')->pluck('due_on')->map->toDateString()->unique()->values()->all())
        ->toBe(['2026-10-13', '2026-10-20', '2026-10-27']);
});

it('rejects a schedule that ends before it starts', function () {
    loginAsAdminInWastePanel();
    ['property' => $property, 'paper' => $paper] = wastePanelFixture();

    Livewire::test(ManageWasteSchedules::class)
        ->callAction('create', data: [
            'property_id' => $property->getKey(),
            'containers' => [$paper->getKey()],
            'source' => 'rule',
            'interval_unit' => 'once',
            'starts_on' => '2026-10-20',
            'ends_on' => '2026-10-10',
        ])
        ->assertHasActionErrors(['ends_on']);
});

it('confirms the due pickups of a collection point on the mobile page with the fill level', function () {
    loginAsAdminInWastePanel();
    ['node' => $node, 'pickup' => $pickup] = wastePanelFixture();

    Livewire::withQueryParams(['node' => $node->getKey()])
        ->test(ConfirmPickupsPage::class)
        ->assertSet('selected', [$pickup->getKey()])
        ->call('setFill', $pickup->getKey(), 50)
        ->callAction('confirmSelected');

    $collection = WasteCollection::query()->where('waste_pickup_id', $pickup->getKey())->sole();

    expect($pickup->refresh()->status)->toBe(WastePickupStatus::Confirmed)
        ->and($pickup->confirmed_via)->toBe(WasteCollectionSource::Scan)
        ->and($collection->fill_level_pct)->toBe(50)
        ->and($collection->mass_source)->toBe(WasteMassSource::Estimated);
});

it('reports a missed pickup and a full container from the scanned container page', function () {
    loginAsAdminInWastePanel();
    ['paper' => $paper, 'pickup' => $pickup] = wastePanelFixture();

    Livewire::withQueryParams(['container' => $paper->getKey()])
        ->test(ConfirmPickupsPage::class)
        ->callAction('missed', data: ['reason' => WasteMissedReason::NotStaged->value], arguments: ['pickup' => $pickup->getKey()])
        ->callAction('requestPickup');

    expect($pickup->refresh()->status)->toBe(WastePickupStatus::Missed)
        ->and(WastePickup::query()->where('waste_container_id', $paper->getKey())->where('status', WastePickupStatus::Requested->value)->exists())->toBeTrue();
});

it('shows grouped pickups in the calendar and plans an extra pickup on a clicked day', function () {
    loginAsAdminInWastePanel();
    ['paper' => $paper, 'second' => $second] = wastePanelFixture();
    WastePickup::factory()->forContainer($second)->create(['due_on' => '2026-10-07']);

    $widget = Livewire::test(PickupCalendarWidget::class)
        ->assertSee('Papier/Pappe · 2 × 1.100 l')
        ->callAction('planExtra', data: ['containers' => [$paper->getKey()]], arguments: ['date' => '2026-10-15']);

    $extra = WastePickup::query()->where('kind', WastePickupKind::Extra->value)->sole();

    expect($extra->due_on->toDateString())->toBe('2026-10-15')
        ->and($extra->status)->toBe(WastePickupStatus::Planned);

    $widget->call('next')->assertDontSee('Papier/Pappe · 2 × 1.100 l');
});

it('saves the cut-off settings of the tenant', function () {
    loginAsAdminInWastePanel();

    Livewire::test(WasteSettingsPage::class)
        ->fillForm(['cutoff_days' => 2, 'cutoff_time' => '07:30', 'request_reminder_days' => 4])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = WasteSettings::forOrganization(defaultOrganization()->getKey());

    expect($settings->cutoff_days)->toBe(2)
        ->and($settings->cutoff_time)->toBe('07:30')
        ->and($settings->request_reminder_days)->toBe(4);
});

it('imports containers and collections from CSV through the recorder', function () {
    loginAsAdminInWastePanel();
    ['pickup' => $pickup] = wastePanelFixture();
    $options = ['organization_id' => defaultOrganization()->getKey()];

    $containers = new WasteContainerImporter(new Import, ['number' => 'Nr', 'property' => 'Objekt', 'location' => 'Standort', 'fraction' => 'Fraktion', 'volume_l' => 'Liter'], $options);
    $containers(['Nr' => 'B-0200', 'Objekt' => 'DEMO-01', 'Standort' => 'Müllplatz Nord', 'Fraktion' => '20 01 01', 'Liter' => '240']);

    $collections = new WasteCollectionImporter(new Import, ['container' => 'Behälter', 'collected_at' => 'Datum', 'mass_kg' => 'Gewicht'], $options);
    $collections(['Behälter' => 'B-0101', 'Datum' => '07.10.2026', 'Gewicht' => '48,5']);

    expect(WasteContainer::query()->where('number', 'B-0200')->sole()->volume_l)->toBe(240)
        ->and($pickup->refresh()->status)->toBe(WastePickupStatus::Confirmed)
        ->and((float) $pickup->collection()->sole()->mass_kg)->toBe(48.5)
        ->and(fn () => $collections(['Behälter' => 'GIBT-ES-NICHT', 'Datum' => '07.10.2026', 'Gewicht' => '1']))
        ->toThrow(RowImportFailedException::class);
});

it('edits a container and requests a pickup from its page', function () {
    loginAsAdminInWastePanel();
    ['paper' => $paper] = wastePanelFixture();

    Livewire::test(EditWasteContainer::class, ['record' => $paper->getKey()])
        ->fillForm(['volume_l' => 660])
        ->call('save')
        ->assertHasNoFormErrors()
        ->callAction('requestPickup');

    expect($paper->refresh()->volume_l)->toBe(660)
        ->and($paper->pickups()->where('status', WastePickupStatus::Requested->value)->exists())->toBeTrue();
});

it('imports the collection point inspection as a form of the waste panel', function () {
    requiresPlugin('form-builder');

    if (! is_file(database_path('templates/muellplatz-begehung.form.json'))) {
        $this->markTestSkipped('Vorlage muellplatz-begehung.form.json ist nicht vorhanden (nicht versioniert).');
    }

    loginAsAdminInWastePanel();

    $this->artisan('forms:import-template', ['file' => 'muellplatz-begehung.form.json', '--organization' => ['demo'], '--publish' => true])
        ->assertSuccessful();

    Livewire::test(FillFormPage::class, ['form' => 'muellplatz-begehung'])
        ->assertOk()
        ->assertSee('Tag der Begehung');
});

it('groups the navigation into collection, master data, analysis and compliance', function () {
    loginAsAdminInWastePanel();

    $navigation = collect(Filament::getNavigation())->mapWithKeys(fn ($group): array => [
        (string) $group->getLabel() => collect($group->getItems())->map(fn ($item): string => $item->getLabel())->values()->all(),
    ]);

    expect($navigation->keys()->all())->toBe(['', 'Abfuhr', 'Stammdaten', 'Auswertung', 'Compliance'])
        ->and($navigation[''])->toBe(['Abfall-Dashboard'])
        ->and(array_slice($navigation['Abfuhr'], 0, 4))->toBe(['Abfuhrkalender', 'Leerungen bestätigen', 'Abfuhrtermine', 'Leerungen'])
        ->and($navigation['Stammdaten'])->toBe(['Behälter', 'Sammelstellen', 'Abfuhrpläne', 'Einstellungen'])
        ->and($navigation['Auswertung'])->toBe(['Ziele', 'Rechnungsprüfung', 'ESG-Berichte', 'Behälteroptimierung', 'Kostenumlage'])
        ->and(array_slice($navigation['Compliance'], 0, 3))->toBe(['Abfall-Dokumentation', 'Register gefährlicher Abfälle', 'Nachweise']);
});

it('creates collection points and preselects them for a new container', function () {
    loginAsAdminInWastePanel();
    ['property' => $property, 'node' => $north] = wastePanelFixture();

    Livewire::test(CollectionPointsPage::class)
        ->assertCanSeeTableRecords([$north])
        ->callTableAction('createCollectionPoint', data: ['property_id' => $property->getKey(), 'name' => 'Müllraum Süd', 'type' => 'room'])
        ->assertHasNoTableActionErrors();

    $node = StructureNode::query()->where('name', 'Müllraum Süd')->sole();

    expect($node->room_use)->toBe(RoomUse::WasteCollection)
        ->and($node->type)->toBe(StructureNodeType::Room)
        ->and(app(CollectionPoints::class)->query()->pluck('name')->sort()->values()->all())->toBe(['Müllplatz Nord', 'Müllraum Süd']);

    Livewire::withQueryParams(['property' => $property->getKey(), 'node' => $node->getKey()])
        ->test(CreateWasteContainer::class)
        ->assertSet('data.property_id', $property->getKey())
        ->assertSet('data.structure_node_id', $node->getKey());
});

it('creates a collection point right from the container form', function () {
    loginAsAdminInWastePanel();
    ['property' => $property] = wastePanelFixture();

    Livewire::test(CreateWasteContainer::class)
        ->fillForm(['property_id' => $property->getKey()])
        ->callAction(TestAction::make('createOption')->schemaComponent('structure_node_id'), data: ['name' => 'Müllplatz West', 'type' => 'outdoor'])
        ->assertHasNoActionErrors()
        ->assertSet('data.structure_node_id', StructureNode::query()->where('name', 'Müllplatz West')->sole()->getKey());
});

it('renders the recycling icon for the waste panel', function () {
    expect(svg('openfm-recycle')->toHtml())->toContain('<path')
        ->and(__('app.panels.waste'))->toBe('Abfallmanagement')
        ->and(__('app.panels.energy'))->toBe('Energiemanagement');
});
