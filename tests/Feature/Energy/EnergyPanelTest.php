<?php

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Enums\PropertyUsageType;
use App\Filament\Energy\Pages\EnergyDashboard;
use App\Filament\Energy\Pages\RecordReadingPage;
use App\Filament\Energy\Resources\EnergyGoalResource\Pages\CreateEnergyGoal;
use App\Filament\Energy\Resources\EnergyGoalResource\Pages\ListEnergyGoals;
use App\Filament\Energy\Resources\MeterReadingResource\Pages\ListMeterReadings;
use App\Filament\Energy\Resources\MeterResource\Pages\CreateMeter;
use App\Filament\Energy\Resources\MeterResource\Pages\ListMeters;
use App\Filament\Energy\Resources\MeterResource\Pages\ViewMeter;
use App\Filament\Energy\Resources\MeterResource\RelationManagers\MeasuredAssetsRelationManager;
use App\Filament\Energy\Resources\MeterResource\RelationManagers\ReadingsRelationManager;
use App\Filament\Energy\Widgets\CalibrationDueWidget;
use App\Filament\Energy\Widgets\ConsumptionByAssetChartWidget;
use App\Filament\Energy\Widgets\ConsumptionTrendChartWidget;
use App\Filament\Energy\Widgets\EnergyFlowWidget;
use App\Filament\Energy\Widgets\EnergyGoalsWidget;
use App\Filament\Energy\Widgets\EnergyStatsWidget;
use App\Filament\Energy\Widgets\PropertyEnergyComparisonWidget;
use App\Filament\Energy\Widgets\SuspiciousReadingsWidget;
use App\Filament\Imports\MeterReadingImporter;
use App\Filament\RelationManagers\AssetMetersRelationManager;
use App\Filament\RelationManagers\PropertyMetersRelationManager;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\ViewProperty;
use App\Models\Asset;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use App\Services\Energy\EnergyGoalEvaluator;
use App\Services\Energy\MeterReadingRecorder;
use Carbon\CarbonImmutable;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * @return array{property: Property, main: Meter, sub: Meter, asset: Asset}
 */
function energyFixture(): array
{
    $property = Property::factory()->create(['property_number' => '1001', 'net_area' => 2000, 'usage_type' => PropertyUsageType::Commercial]);
    $main = Meter::factory()->main()->create(['property_id' => $property->getKey(), 'name' => 'Hauptzähler Strom', 'number' => 'HZ-1']);
    $sub = Meter::factory()->childOf($main)->create(['name' => 'UZ-03 Kälteanlage', 'number' => 'UZ-3', 'calibration_valid_until' => now()->addDays(30)]);
    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Kälteanlage KA-1']);
    $sub->measuredAssets()->attach($asset->getKey(), ['share' => 100]);

    $recorder = app(MeterReadingRecorder::class);
    $start = CarbonImmutable::today()->subDays(60);
    $recorder->record($main, $start, 1000, MeterReadingSource::Import);
    $recorder->record($main, CarbonImmutable::today(), 7000, MeterReadingSource::Import);
    $recorder->record($sub, $start, 0, MeterReadingSource::Api);
    $recorder->record($sub, CarbonImmutable::today(), 1800, MeterReadingSource::Api);

    return compact('property', 'main', 'sub', 'asset');
}

it('lets admins, facility managers and users into the energy panel but not reporters', function () {
    loginAsAdmin();
    $panel = Filament::getPanel('energy');

    $reporter = User::factory()->create();
    $reporter->organizations()->attach(defaultOrganization());
    $reporter->assignRole('Melder');

    $facility = User::factory()->create();
    $facility->organizations()->attach(defaultOrganization());
    $facility->assignRole('FM');

    expect(auth()->user()->canAccessPanel($panel))->toBeTrue()
        ->and($facility->canAccessPanel($panel))->toBeTrue()
        ->and($reporter->canAccessPanel($panel))->toBeFalse();
});

it('renders the energy dashboard and all its widgets with data', function () {
    loginAsAdminInEnergyPanel();
    ['property' => $property] = energyFixture();
    EnergyGoal::factory()->create(['name' => 'Strom Karlsruhe', 'goalable_type' => $property->getMorphClass(), 'goalable_id' => $property->getKey()]);
    $suspicious = Meter::factory()->create(['name' => 'Wasser Keller']);
    app(MeterReadingRecorder::class)->record($suspicious, CarbonImmutable::today()->subDay(), 100, MeterReadingSource::Manual);
    app(MeterReadingRecorder::class)->record($suspicious, CarbonImmutable::today(), 50, MeterReadingSource::Manual);

    Livewire::test(EnergyDashboard::class)->assertSuccessful();
    Livewire::test(EnergyStatsWidget::class)->assertSuccessful()->assertSee(__('energy.stats.energy_in_period'));
    Livewire::test(ConsumptionTrendChartWidget::class)->assertSuccessful()->set('filter', 'heat')->assertSuccessful();
    Livewire::test(ConsumptionByAssetChartWidget::class)->assertSuccessful();
    Livewire::test(EnergyGoalsWidget::class)->assertSuccessful()->assertSee('Strom Karlsruhe');
    Livewire::test(EnergyFlowWidget::class)
        ->assertSuccessful()
        ->assertSet('propertyId', $property->getKey())
        ->assertSee('Kälteanlage KA-1')
        ->assertSee(__('energy.flow.unassigned'));
    Livewire::test(PropertyEnergyComparisonWidget::class)->assertSuccessful()->assertSee('1001');
    Livewire::test(CalibrationDueWidget::class)->assertSuccessful()->assertSee('UZ-03 Kälteanlage');
    Livewire::test(SuspiciousReadingsWidget::class)->assertSuccessful()->assertSee('Wasser Keller');
});

it('lists meters and creates a calculated meter from a formula', function () {
    loginAsAdminInEnergyPanel();
    ['property' => $property, 'main' => $main, 'sub' => $sub] = energyFixture();

    Livewire::test(ListMeters::class)
        ->assertCanSeeTableRecords([$main, $sub])
        ->filterTable('kind', MeterKind::Main->value)
        ->assertCanSeeTableRecords([$main])
        ->assertCanNotSeeTableRecords([$sub]);

    Livewire::test(CreateMeter::class)
        ->fillForm([
            'property_id' => $property->getKey(),
            'name' => 'Rest Strom',
            'medium' => EnergyMedium::Electricity,
            'kind' => MeterKind::Virtual,
            'parent_id' => $main->getKey(),
            'formula' => [
                ['sign' => '1', 'meter_id' => $main->getKey()],
                ['sign' => '-1', 'meter_id' => $sub->getKey()],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $rest = Meter::query()->where('name', 'Rest Strom')->firstOrFail();

    expect($rest->kind)->toBe(MeterKind::Virtual)
        ->and(round((float) $rest->dailyValues()->sum('consumption')))->toBe(4200.0);
});

it('records a reading on the meter page through the same pipeline and warns about outliers', function () {
    loginAsAdminInEnergyPanel();
    ['sub' => $sub] = energyFixture();

    Livewire::test(ViewMeter::class, ['record' => $sub->getKey()])
        ->assertSuccessful()
        ->assertSee('UZ-03 Kälteanlage')
        ->callAction('recordReading', data: ['read_at' => now()->addHour()->format('Y-m-d H:i:s'), 'value' => 1830])
        ->assertHasNoActionErrors();

    expect($sub->readings()->count())->toBe(3)
        ->and($sub->latestReading()->first()->source)->toBe(MeterReadingSource::Manual);

    Livewire::test(ReadingsRelationManager::class, ['ownerRecord' => $sub, 'pageClass' => ViewMeter::class])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($sub->readings()->get())
        ->callTableAction('delete', $sub->latestReading()->first());

    expect($sub->readings()->count())->toBe(2);
});

it('attaches measured assets with a share', function () {
    loginAsAdminInEnergyPanel();
    ['property' => $property, 'main' => $main] = energyFixture();
    $vent = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lüftung RLT-1']);
    $foreign = Asset::factory()->create(['name' => 'Fremde Anlage']);

    Livewire::test(MeasuredAssetsRelationManager::class, ['ownerRecord' => $main, 'pageClass' => ViewMeter::class])
        ->callAction(TestAction::make('attach')->table(), data: ['recordId' => $vent->getKey(), 'share' => 70])
        ->assertHasNoActionErrors()
        ->assertCanSeeTableRecords([$vent]);

    expect((float) $main->measuredAssets()->whereKey($vent->getKey())->first()->pivot->share)->toBe(70.0)
        ->and($foreign->measuringMeters()->count())->toBe(0);
});

it('records readings on the mobile page, including a meter preselected by link', function () {
    loginAsAdminInEnergyPanel();
    ['main' => $main] = energyFixture();

    Livewire::withQueryParams(['meter' => $main->getKey()])
        ->test(RecordReadingPage::class)
        ->assertSet('data.meter_id', $main->getKey())
        ->fillForm(['value' => 7100, 'read_at' => now()->addHour()->format('Y-m-d H:i:s')])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertSet('data.meter_id', null);

    expect($main->readings()->count())->toBe(3)
        ->and((float) $main->latestReading()->first()->value)->toBe(7100.0);
});

it('lists all readings and imports readings by meter number', function () {
    loginAsAdminInEnergyPanel();
    ['main' => $main] = energyFixture();

    Livewire::test(ListMeterReadings::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(MeterReading::query()->get())
        ->filterTable('source', MeterReadingSource::Api->value)
        ->assertCanNotSeeTableRecords($main->readings()->get());

    $importer = new MeterReadingImporter(new Import, ['meter' => 'Zähler', 'read_at' => 'Datum', 'value' => 'Stand'], ['organization_id' => defaultOrganization()->getKey()]);
    $importer(['Zähler' => 'HZ-1', 'Datum' => now()->addDay()->format('d.m.Y H:i'), 'Stand' => '7.100,5']);

    expect((float) $main->latestReading()->first()->value)->toBe(7100.5)
        ->and($main->latestReading()->first()->source)->toBe(MeterReadingSource::Import)
        ->and(fn () => $importer(['Zähler' => 'GIBT-ES-NICHT', 'Datum' => '01.01.2026', 'Stand' => '1']))
        ->toThrow(RowImportFailedException::class);
});

it('creates goals and shows their progress', function () {
    loginAsAdminInEnergyPanel();
    ['property' => $property] = energyFixture();

    Livewire::test(CreateEnergyGoal::class)
        ->fillForm([
            'name' => 'Strom 1001',
            'goalable_type' => $property->getMorphClass(),
            'goalable_id' => $property->getKey(),
            'medium' => EnergyMedium::Electricity,
            'type' => EnergyGoalType::MaxConsumption,
            'period' => EnergyGoalPeriod::Year,
            'year' => now()->year,
            'target_value' => 100,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $goal = EnergyGoal::query()->where('name', 'Strom 1001')->firstOrFail();

    Livewire::test(ListEnergyGoals::class)
        ->assertCanSeeTableRecords([$goal])
        ->assertSee(__('energy.goal_status.crit'));
});

it('evaluates goals with forecast, baseline and intensity', function () {
    loginAsAdmin();
    $property = Property::factory()->create(['net_area' => 100]);
    $meter = Meter::factory()->main()->create(['property_id' => $property->getKey()]);
    $recorder = app(MeterReadingRecorder::class);
    $recorder->record($meter, CarbonImmutable::parse('2025-01-01'), 0, MeterReadingSource::Import);
    $recorder->record($meter, CarbonImmutable::parse('2026-01-01'), 36500, MeterReadingSource::Import);
    $recorder->record($meter, CarbonImmutable::parse('2026-04-11'), 36500 + 100 * 100, MeterReadingSource::Import);

    $evaluator = app(EnergyGoalEvaluator::class);
    $today = CarbonImmutable::parse('2026-04-10');
    $scope = ['goalable_type' => $property->getMorphClass(), 'goalable_id' => $property->getKey(), 'year' => 2026];

    $max = $evaluator->evaluate(EnergyGoal::factory()->create([...$scope, 'target_value' => 40000]), $today);
    $reduction = $evaluator->evaluate(EnergyGoal::factory()->create([...$scope, 'type' => EnergyGoalType::Reduction, 'target_value' => 10, 'baseline_year' => 2025]), $today);
    $intensity = $evaluator->evaluate(EnergyGoal::factory()->create([...$scope, 'type' => EnergyGoalType::MaxIntensity, 'target_value' => 300]), $today);
    $month = $evaluator->evaluate(EnergyGoal::factory()->create([...$scope, 'period' => EnergyGoalPeriod::Month, 'target_value' => 2500]), $today);

    expect($max)->toMatchArray(['actual' => 10000.0, 'forecast' => 36500.0, 'target' => 40000.0, 'status' => 'good'])
        ->and($reduction)->toMatchArray(['target' => 32850.0, 'status' => 'warn'])
        ->and($intensity)->toMatchArray(['actual' => 100.0, 'forecast' => 365.0, 'unit' => 'kWh/m²', 'status' => 'warn'])
        ->and($month)->toMatchArray(['actual' => 1000.0, 'forecast' => 3000.0, 'status' => 'warn']);
});

it('shows the usage type and the energy tabs on properties and assets', function () {
    loginAsAdminInFmPanel();
    ['property' => $property, 'sub' => $sub, 'asset' => $asset] = energyFixture();

    Livewire::test(CreateProperty::class)
        ->fillForm(['property_number' => '2001', 'usage_type' => PropertyUsageType::Residential])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Property::query()->where('property_number', '2001')->value('usage_type'))->toBe(PropertyUsageType::Residential);

    Livewire::test(ViewProperty::class, ['record' => $property->getKey()])->assertSuccessful();
    Livewire::test(PropertyMetersRelationManager::class, ['ownerRecord' => $property, 'pageClass' => ViewProperty::class])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$sub]);
    Livewire::test(AssetMetersRelationManager::class, ['ownerRecord' => $asset, 'pageClass' => ViewAsset::class])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$sub])
        ->assertSee('1.800');
});

it('flags readings that are lower than the previous one, also from the API', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();
    $recorder = app(MeterReadingRecorder::class);

    $recorder->record($meter, CarbonImmutable::parse('2026-01-01'), 10, MeterReadingSource::Api);
    $reading = $recorder->record($meter, CarbonImmutable::parse('2026-01-02'), 5, MeterReadingSource::Api);

    expect($reading->status)->toBe(MeterReadingStatus::Suspicious);
});
