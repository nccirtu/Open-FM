<?php

use App\Enums\AssetServiceStatus;
use App\Enums\CostEntryKind;
use App\Enums\StructureNodeType;
use App\Enums\WasteCollectionSource;
use App\Enums\WasteEsrsCategory;
use App\Enums\WasteGoalType;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Enums\WasteScheduleSource;
use App\Filament\Integrations\MapWidget\WasteContainerMapWidget;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\WasteCollectionsRelationManager;
use App\Filament\Waste\Pages\CostAllocationPage;
use App\Filament\Waste\Pages\RightsizingPage;
use App\Filament\Waste\Pages\WasteEsgReportPage;
use App\Filament\Waste\Resources\WasteContainers\Pages\EditWasteContainer;
use App\Filament\Waste\Resources\WasteContainers\RelationManagers\FillReadingsRelationManager;
use App\Models\Asset;
use App\Models\CostEntry;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteContainerType;
use App\Models\WasteEmissionFactor;
use App\Models\WasteFraction;
use App\Models\WasteGoal;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Models\WasteTreatmentCategory;
use App\Services\Waste\ConstructionWasteSummary;
use App\Services\Waste\WasteCollectionRecorder;
use App\Services\Waste\WasteCostAllocation;
use App\Services\Waste\WasteEsgReport;
use App\Services\Waste\WasteGoalEvaluator;
use App\Services\Waste\WasteLookupDefaults;
use App\Services\Waste\WasteReport;
use App\Services\Waste\WasteRightsizing;
use App\Support\WasteFilters;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

function insightsFixture(): Property
{
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));

    $property = Property::factory()->create(['property_number' => 'DEMO-01', 'net_area' => 1000]);
    $recorder = app(WasteCollectionRecorder::class);
    $recorder->recordForProperty($property, WasteFraction::factory()->paper()->create(), CarbonImmutable::parse('2026-03-01'), 600, WasteCollectionSource::Import);
    $recorder->recordForProperty($property, WasteFraction::factory()->mixed()->create(), CarbonImmutable::parse('2026-03-01'), 300, WasteCollectionSource::Import);
    $recorder->recordForProperty($property, WasteFraction::factory()->hazardous()->treatment('D10')->create(), CarbonImmutable::parse('2026-04-01'), 100, WasteCollectionSource::Import);

    return $property;
}

it('calculates co2e with the most specific emission factor and its coverage', function () {
    loginAsAdminInWastePanel();
    $property = insightsFixture();
    $paper = WasteFraction::query()->where('code', '20 01 01')->sole();
    $recycling = WasteTreatmentCategory::query()->where('key', 'recycling')->sole();
    $other = WasteTreatmentCategory::query()->where('key', 'other_recovery')->sole();
    WasteEmissionFactor::factory()->create(['waste_fraction_id' => null, 'waste_treatment_category_id' => $recycling->getKey(), 'kg_co2e_per_t' => 50]);
    WasteEmissionFactor::factory()->create(['waste_fraction_id' => $paper->getKey(), 'waste_treatment_category_id' => $recycling->getKey(), 'kg_co2e_per_t' => 20, 'valid_year' => 2025]);
    WasteEmissionFactor::factory()->create(['waste_fraction_id' => $paper->getKey(), 'waste_treatment_category_id' => $recycling->getKey(), 'kg_co2e_per_t' => 99, 'valid_year' => 2027]);
    WasteEmissionFactor::factory()->create(['waste_fraction_id' => null, 'waste_treatment_category_id' => $other->getKey(), 'kg_co2e_per_t' => 400]);

    $co2e = app(WasteReport::class)->co2e(WasteFilters::fromArray(['period' => 'this_year', 'property_id' => $property->getKey()]));

    expect($co2e['kg'])->toBe(132.0)
        ->and($co2e['coverage'])->toBe(90.0);
});

it('reports esrs, vsme, gresb and leed figures by the esrs category of the treatments', function () {
    loginAsAdminInWastePanel();
    $property = insightsFixture();
    Property::factory()->create(['net_area' => 3000]);
    WasteTreatmentCategory::query()->where('key', 'incineration')->update(['esrs_category' => null]);

    $report = app(WasteEsgReport::class)->build(2026, null);

    expect($report['mass'])->toBe(['total' => 1.0, 'hazardous' => 0.1, 'non_hazardous' => 0.9])
        ->and($report['esrs'][WasteEsrsCategory::Recycling->value]['non_hazardous'])->toBe(0.6)
        ->and($report['esrs'][WasteEsrsCategory::OtherRecovery->value]['total'])->toBe(0.3)
        ->and($report['esrs']['unknown'])->toBe(['hazardous' => 0.1, 'non_hazardous' => 0.0, 'total' => 0.1])
        ->and($report['diverted']['total'])->toBe(0.9)
        ->and($report['non_recycled']['total'])->toBe(0.4)
        ->and($report['diversion_rate'])->toBe(60.0)
        ->and($report['coverage'])->toMatchArray(['properties' => 1, 'properties_total' => 2, 'share' => 50.0, 'area_share' => 25.0])
        ->and(app(WasteEsgReport::class)->csvRows($report))->toContain(['LEED O+M', __('waste.esg.diversion_rate'), '%', 60.0]);

    Livewire::test(WasteEsgReportPage::class, ['year' => 2026, 'property' => $property->getKey()])
        ->assertSee('ESRS E5-5')
        ->assertSee('60,0 %');
});

it('summarises construction waste by the taxonomy quota, the small quantity limit and the building age', function () {
    loginAsAdmin();
    app(WasteLookupDefaults::class)->seed(defaultOrganization());
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    $project = Project::factory()->create(['property_id' => Property::factory()->create(['construction_year' => 1978])->getKey()]);
    $concrete = WasteFraction::factory()->group('construction')->treatment('R5')->create(['name' => 'Beton', 'code' => '17 01 01']);
    $mixed = WasteFraction::factory()->group('construction')->treatment('D1')->create(['name' => 'Gemischte Bauabfälle', 'code' => '17 09 04']);
    $soil = WasteFraction::factory()->group('construction')->treatment('D1')->create(['name' => 'Boden und Steine', 'code' => '17 05 04']);

    Livewire::test(WasteCollectionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callTableAction('recordConstructionWaste', data: ['fraction' => $concrete->getKey(), 'collected_at' => '2026-10-01 08:00', 'mass_kg' => 8000, 'volume_m3' => 6])
        ->callTableAction('recordConstructionWaste', data: ['fraction' => $mixed->getKey(), 'collected_at' => '2026-10-02 08:00', 'mass_kg' => 2000, 'volume_m3' => 4])
        ->callTableAction('recordConstructionWaste', data: ['fraction' => $soil->getKey(), 'collected_at' => '2026-10-03 08:00', 'mass_kg' => 20000, 'volume_m3' => 10])
        ->assertHasNoTableActionErrors()
        ->assertSee(__('waste.construction.title'));

    expect($project->wasteCollections()->count())->toBe(3)
        ->and(app(ConstructionWasteSummary::class)->for($project))->toMatchArray([
            'base_t' => 10.0,
            'recovered_t' => 8.0,
            'recovery_rate' => 80.0,
            'met' => true,
            'volume_m3' => 20.0,
            'small_quantity' => false,
            'asbestos_risk' => true,
        ]);
});

it('disposes an asset as waste and marks it as disposed', function () {
    loginAsAdminInFmPanel();
    app(WasteLookupDefaults::class)->seed(defaultOrganization());
    $asset = Asset::factory()->create(['name' => 'Kühlschrank Lager']);
    $fraction = WasteFraction::factory()->group('electrical')->treatment('R4')->create(['name' => 'Elektroaltgeräte', 'code' => '20 01 36']);

    Livewire::test(ViewAsset::class, ['record' => $asset->getRouteKey()])
        ->callAction('disposeAsset', [
            'fraction' => $fraction->getKey(),
            'collected_at' => '2026-10-01',
            'mass_kg' => 45,
            'batteries_removed' => true,
        ])
        ->assertHasNoActionErrors();

    expect($asset->refresh()->service_status)->toBe(AssetServiceStatus::Disposed)
        ->and($asset->decommissioned_at->toDateString())->toBe('2026-10-01')
        ->and(WasteCollection::query()->where('asset_id', $asset->getKey())->sole())
        ->mass_kg->toEqual(45)
        ->batteries_removed->toBeTrue()
        ->property_id->toBe($asset->property_id);
});

it('recommends container sizes and rhythms from fill levels and extra pickups', function () {
    loginAsAdminInWastePanel();
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    $fraction = WasteFraction::factory()->paper()->create();
    $container = fn (string $number): WasteContainer => WasteContainer::factory()->create(['number' => $number, 'waste_fraction_id' => $fraction->getKey(), 'volume_l' => 1100, 'price_per_pickup' => 20]);
    [$low, $full, $fine, $sparse] = [$container('LOW'), $container('FULL'), $container('FINE'), $container('SPARSE')];
    WasteSchedule::factory()->create(['property_id' => $low->property_id])->containers()->attach($low);
    $recorder = app(WasteCollectionRecorder::class);

    foreach ([[$low, 30], [$full, 95], [$fine, 70]] as [$record, $fill]) {
        foreach (['2026-08-03', '2026-09-07', '2026-10-05'] as $date) {
            $recorder->recordForContainer($record, CarbonImmutable::parse($date), WasteCollectionSource::Scan, fillLevelPct: $fill);
        }
    }
    $recorder->recordForContainer($sparse, CarbonImmutable::parse('2026-10-05'), WasteCollectionSource::Scan, fillLevelPct: 40);
    WastePickup::factory()->forContainer($fine)->create(['kind' => WastePickupKind::Extra, 'status' => WastePickupStatus::Confirmed, 'due_on' => '2026-09-20']);

    $rows = app(WasteRightsizing::class)->recommendations();

    expect($rows[$low->getKey()])->toMatchArray(['recommendation' => 'reduce', 'average_fill' => 30.0, 'suggested_volume' => 660, 'saving' => 122.0])
        ->and($rows[$full->getKey()])->toMatchArray(['recommendation' => 'increase', 'suggested_volume' => null])
        ->and($rows[$fine->getKey()])->toMatchArray(['recommendation' => 'increase', 'extra' => 1])
        ->and($rows[$sparse->getKey()]['recommendation'])->toBe('no_data');

    Livewire::test(RightsizingPage::class)
        ->assertSee('LOW')
        ->assertSee(__('waste.rightsizing.status.reduce'));
});

it('estimates the fill level of weighed collections from volume and density except for compactors', function () {
    loginAsAdminInWastePanel();
    $fraction = WasteFraction::factory()->paper()->create();
    $container = WasteContainer::factory()->create(['volume_l' => 1100, 'waste_fraction_id' => $fraction->getKey()]);
    $compactor = WasteContainer::factory()->create(['volume_l' => 20000, 'waste_fraction_id' => $fraction->getKey(), 'waste_container_type_id' => WasteContainerType::query()->where('key', 'compactor')->value('id')]);
    $recorder = app(WasteCollectionRecorder::class);

    expect(app(WasteRightsizing::class)->fillLevel($container, $recorder->recordForContainer($container, now(), WasteCollectionSource::Import, massKg: 33)))->toBe(50.0)
        ->and(app(WasteRightsizing::class)->fillLevel($compactor, $recorder->recordForContainer($compactor, now(), WasteCollectionSource::Import, massKg: 4000)))->toBeNull();
});

it('does not count on-demand pickups of containers without a rhythm as extra pickups', function () {
    loginAsAdminInWastePanel();
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    $container = WasteContainer::factory()->create();
    WasteSchedule::factory()->create(['property_id' => $container->property_id, 'source' => WasteScheduleSource::OnDemand])->containers()->attach($container);
    WastePickup::factory()->forContainer($container)->count(2)->sequence(['due_on' => '2026-09-01'], ['due_on' => '2026-10-01'])->create(['kind' => WastePickupKind::OnDemand, 'status' => WastePickupStatus::Confirmed]);

    expect(app(WasteRightsizing::class)->recommendations()[$container->getKey()])->toMatchArray(['extra' => 0, 'recommendation' => 'no_data']);
});

it('allocates waste costs to the units by area or in equal shares', function () {
    loginAsAdminInWastePanel();
    $property = Property::factory()->create();
    $container = WasteContainer::factory()->create(['property_id' => $property->getKey()]);
    $shop = StructureNode::factory()->create(['property_id' => $property->getKey(), 'type' => StructureNodeType::Unit, 'name' => 'Laden', 'area' => 300]);
    $office = StructureNode::factory()->create(['property_id' => $property->getKey(), 'type' => StructureNodeType::Unit, 'name' => 'Büro', 'area' => null]);
    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $office->getKey(), 'area' => 100]);
    $cost = new CostEntry(['costable_type' => $container->getMorphClass(), 'costable_id' => $container->getKey(), 'kind' => CostEntryKind::Actual, 'amount' => 476, 'tax_rate' => 19, 'net_amount' => 400, 'booked_on' => '2025-12-31']);
    $cost->organization_id = $container->organization_id;
    $cost->save();
    $allocation = app(WasteCostAllocation::class);
    $year = [CarbonImmutable::parse('2025-01-01'), CarbonImmutable::parse('2025-12-31')];

    $byArea = $allocation->allocate($property, ...$year);
    $equal = $allocation->allocate($property, ...$year, method: 'equal');

    expect($byArea['total'])->toBe(400.0)
        ->and($byArea['rows'][$shop->getKey()])->toMatchArray(['area' => 300.0, 'amount' => 300.0])
        ->and($byArea['rows'][$office->getKey()])->toMatchArray(['area' => 100.0, 'amount' => 100.0])
        ->and($equal['rows'][$office->getKey()]['amount'])->toBe(200.0);

    Livewire::test(CostAllocationPage::class, ['property' => $property->getKey(), 'year' => 2025])
        ->assertSee('Laden')
        ->assertSee('300,00');
});

it('records a fill level by hand through the sensor pipeline', function () {
    loginAsAdminInWastePanel();
    $container = WasteContainer::factory()->create();

    Livewire::test(FillReadingsRelationManager::class, ['ownerRecord' => $container, 'pageClass' => EditWasteContainer::class])
        ->callTableAction('recordReading', data: ['measured_at' => now()->toDateTimeString(), 'fill_level_pct' => 95])
        ->assertHasNoTableActionErrors();

    expect($container->fillReadings()->sole()->fill_level_pct)->toBe(95)
        ->and(WastePickup::query()->where('waste_container_id', $container->getKey())->sole()->status)->toBe(WastePickupStatus::Requested);
});

it('shows containers on the map grouped by location', function () {
    requiresPlugin('map-widget');
    loginAsAdminInWastePanel();
    $property = Property::factory()->create(['latitude' => 50.1, 'longitude' => 8.6]);
    WasteContainer::factory()->create(['property_id' => $property->getKey(), 'number' => 'A-1']);
    WasteContainer::factory()->create(['property_id' => $property->getKey(), 'number' => 'A-2']);
    WasteContainer::factory()->create(['property_id' => $property->getKey(), 'number' => 'B-1', 'latitude' => 50.2, 'longitude' => 8.7]);

    $popups = collect((new WasteContainerMapWidget)->getMarkers())->map(fn ($marker): string => $marker->toArray()['popup']);

    expect($popups)->toHaveCount(2)
        ->and($popups->first(fn (string $popup): bool => str_contains($popup, 'A-1')))->toContain('A-2')->not->toContain('B-1');
});

it('evaluates construction waste of projects separately from operational waste', function () {
    loginAsAdminInWastePanel();
    test()->travelTo(CarbonImmutable::parse('2026-10-15 10:00'));
    $property = Property::factory()->create();
    $project = Project::factory()->create(['property_id' => $property->getKey()]);
    $paper = WasteFraction::factory()->paper()->create();
    $concrete = WasteFraction::factory()->group('construction')->treatment('R5')->create(['name' => 'Beton', 'code' => '17 01 01']);
    $recorder = app(WasteCollectionRecorder::class);
    $recorder->recordForProperty($property, $paper, CarbonImmutable::parse('2026-03-01'), 1000, WasteCollectionSource::Import);
    $recorder->recordForProperty($property, $concrete, CarbonImmutable::parse('2026-03-01'), 500, WasteCollectionSource::Manual, ['project_id' => $project->getKey()]);
    $report = app(WasteReport::class);
    $filters = WasteFilters::fromArray(['period' => 'this_year', 'property_id' => $property->getKey()]);

    expect($filters->kind())->toBe(WasteFilters::KIND_OPERATIONS)
        ->and($report->totalMass($filters))->toBe(1000.0)
        ->and($report->totalMass($filters->withKind(WasteFilters::KIND_CONSTRUCTION)))->toBe(500.0)
        ->and($report->totalMass($filters->withKind(WasteFilters::KIND_ALL)))->toBe(1500.0)
        ->and(app(WasteGoalEvaluator::class)->evaluate(WasteGoal::factory()->create(['property_id' => $property->getKey(), 'type' => WasteGoalType::MaxMass, 'year' => 2026, 'target_value' => 5]))['actual'])->toBe(1.0)
        ->and(app(WasteEsgReport::class)->build(2026, $property->getKey()))->toMatchArray(['kind' => 'all', 'construction_t' => 0.5])
        ->and(app(WasteEsgReport::class)->build(2026, $property->getKey())['mass']['total'])->toBe(1.5)
        ->and(app(WasteEsgReport::class)->build(2026, $property->getKey(), kind: WasteFilters::KIND_OPERATIONS)['mass']['total'])->toBe(1.0);
});
