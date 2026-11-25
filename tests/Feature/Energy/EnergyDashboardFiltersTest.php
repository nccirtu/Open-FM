<?php

use App\Enums\MeterReadingSource;
use App\Enums\PropertyUsageType;
use App\Filament\Energy\Pages\EnergyDashboard;
use App\Filament\Energy\Widgets\CalibrationDueWidget;
use App\Filament\Energy\Widgets\ConsumptionTrendChartWidget;
use App\Filament\Energy\Widgets\EnergyFlowWidget;
use App\Filament\Energy\Widgets\EnergyGoalsWidget;
use App\Filament\Energy\Widgets\EnergyStatsWidget;
use App\Filament\Energy\Widgets\PropertyEnergyComparisonWidget;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\Property;
use App\Services\Energy\MeterReadingRecorder;
use App\Support\EnergyFilters;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

/**
 * @return array{office: Property, home: Property}
 */
function filteredEnergyFixture(): array
{
    $recorder = app(MeterReadingRecorder::class);
    $properties = [];

    foreach (['office' => ['A-100', PropertyUsageType::Commercial, 1000], 'home' => ['W-200', PropertyUsageType::Residential, 300]] as $key => [$number, $usage, $consumption]) {
        $property = Property::factory()->create(['property_number' => $number, 'usage_type' => $usage]);
        $meter = Meter::factory()->main()->create(['property_id' => $property->getKey(), 'name' => 'Strom '.$number, 'calibration_valid_until' => now()->addDays(20)]);
        $recorder->record($meter, CarbonImmutable::today()->subDays(10), 0, MeterReadingSource::Api);
        $recorder->record($meter, CarbonImmutable::today(), $consumption, MeterReadingSource::Api);
        $properties[$key] = $property;
    }

    return $properties;
}

it('scopes the dashboard widgets to the property stored by the dashboard filters', function () {
    loginAsAdminInEnergyPanel();
    ['office' => $office, 'home' => $home] = filteredEnergyFixture();

    session()->put(EnergyFilters::sessionKey(), ['period' => 'last_30_days', 'property_id' => $office->getKey()]);

    Livewire::test(EnergyStatsWidget::class)->assertSee('1.000 kWh')->assertDontSee('1.300 kWh');
    Livewire::test(PropertyEnergyComparisonWidget::class)->assertSee('A-100')->assertDontSee('W-200');
    Livewire::test(CalibrationDueWidget::class)->assertSee('Strom A-100')->assertDontSee('Strom W-200');
    Livewire::test(EnergyFlowWidget::class)->assertSet('propertyId', $office->getKey())->assertSee('Strom A-100');

    Livewire::test(EnergyStatsWidget::class)
        ->dispatch(EnergyFilters::EVENT, filters: ['period' => 'last_30_days', 'property_id' => $home->getKey()])
        ->assertSee('300 kWh')
        ->assertDontSee('1.000 kWh');
});

it('filters by usage type and covers the whole organization without a selection', function () {
    loginAsAdminInEnergyPanel();
    filteredEnergyFixture();

    session()->put(EnergyFilters::sessionKey(), ['period' => 'last_30_days', 'usage_type' => PropertyUsageType::Residential->value]);

    Livewire::test(PropertyEnergyComparisonWidget::class)->assertSee('W-200')->assertDontSee('A-100');

    session()->put(EnergyFilters::sessionKey(), ['period' => 'last_30_days']);

    Livewire::test(EnergyStatsWidget::class)->assertSee('1.300 kWh');
    Livewire::test(PropertyEnergyComparisonWidget::class)->assertSee('W-200')->assertSee('A-100');
});

it('limits the period of all widgets', function () {
    loginAsAdminInEnergyPanel();
    filteredEnergyFixture();

    session()->put(EnergyFilters::sessionKey(), ['period' => 'last_year']);

    Livewire::test(EnergyStatsWidget::class)->assertSee('0 kWh')->assertDontSee('1.300 kWh');
    Livewire::test(ConsumptionTrendChartWidget::class)->assertSuccessful()->assertSee(__('energy.charts.consumption_trend'));

    session()->put(EnergyFilters::sessionKey(), ['period' => 'this_month']);

    Livewire::test(ConsumptionTrendChartWidget::class)->assertSee(__('energy.charts.consumption_per_day'));
});

it('shows goals of the selected level and year only', function () {
    loginAsAdminInEnergyPanel();
    ['office' => $office, 'home' => $home] = filteredEnergyFixture();
    EnergyGoal::factory()->create(['name' => 'Ziel Büro', 'goalable_type' => $office->getMorphClass(), 'goalable_id' => $office->getKey(), 'year' => now()->year]);
    EnergyGoal::factory()->create(['name' => 'Ziel Wohnen', 'goalable_type' => $home->getMorphClass(), 'goalable_id' => $home->getKey(), 'year' => now()->year]);
    EnergyGoal::factory()->create(['name' => 'Ziel Mandant', 'goalable_type' => null, 'goalable_id' => null, 'year' => now()->year]);
    EnergyGoal::factory()->create(['name' => 'Ziel Vorjahr', 'goalable_type' => $office->getMorphClass(), 'goalable_id' => $office->getKey(), 'year' => now()->year - 1]);

    session()->put(EnergyFilters::sessionKey(), ['period' => 'this_year', 'property_id' => $office->getKey()]);

    Livewire::test(EnergyGoalsWidget::class)->assertSee('Ziel Büro')->assertDontSee('Ziel Wohnen')->assertDontSee('Ziel Mandant')->assertDontSee('Ziel Vorjahr');

    session()->put(EnergyFilters::sessionKey(), ['period' => 'last_year']);

    Livewire::test(EnergyGoalsWidget::class)->assertSee('Ziel Vorjahr')->assertDontSee('Ziel Büro');
});

it('stores the filters per organization and notifies the widgets without re-rendering the grid', function () {
    loginAsAdminInEnergyPanel();
    ['office' => $office] = filteredEnergyFixture();

    Livewire::test(EnergyDashboard::class)
        ->assertSet('filters.period', EnergyFilters::DEFAULT_PERIOD)
        ->set('filters.property_id', $office->getKey())
        ->assertDispatched(EnergyFilters::EVENT);

    expect(session()->get(EnergyFilters::sessionKey()))->toMatchArray(['property_id' => $office->getKey()])
        ->and(EnergyFilters::sessionKey())->toEndWith(defaultOrganization()->getKey());
});

it('resolves the dashboard periods', function (array $filters, string $from, string $to) {
    $resolved = EnergyFilters::fromArray($filters, CarbonImmutable::parse('2026-10-07'));

    expect($resolved->from->toDateString())->toBe($from)
        ->and($resolved->to->toDateString())->toBe($to);
})->with([
    'default' => [[], '2026-01-01', '2026-10-07'],
    'this month' => [['period' => 'this_month'], '2026-10-01', '2026-10-07'],
    'last month' => [['period' => 'last_month'], '2026-09-01', '2026-09-30'],
    'last 30 days' => [['period' => 'last_30_days'], '2026-09-08', '2026-10-07'],
    'this quarter' => [['period' => 'this_quarter'], '2026-10-01', '2026-10-07'],
    'last year' => [['period' => 'last_year'], '2025-01-01', '2025-12-31'],
    'last 12 months' => [['period' => 'last_12_months'], '2025-11-01', '2026-10-07'],
    'custom' => [['period' => 'custom', 'from' => '2026-03-01', 'until' => '2026-03-31'], '2026-03-01', '2026-03-31'],
    'custom capped at today' => [['period' => 'custom', 'from' => '2026-09-01', 'until' => '2027-01-31'], '2026-09-01', '2026-10-07'],
    'unknown period' => [['period' => 'forever'], '2026-01-01', '2026-10-07'],
]);
