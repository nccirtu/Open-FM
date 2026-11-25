<?php

use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Models\Asset;
use App\Models\Meter;
use App\Models\MeterDailyValue;
use App\Models\Property;
use App\Services\Energy\ConsumptionAggregator;
use App\Services\Energy\EnergyFlow;
use App\Services\Energy\EnergyReport;
use App\Services\Energy\MeterReadingRecorder;
use App\Support\SankeyLayout;
use Carbon\CarbonImmutable;

function recordReading(Meter $meter, string $readAt, float $value, MeterReadingSource $source = MeterReadingSource::Manual): void
{
    app(MeterReadingRecorder::class)->record($meter, CarbonImmutable::parse($readAt), $value, $source);
}

/**
 * @return array<string, float>
 */
function dailyValues(Meter $meter): array
{
    return $meter->dailyValues()->orderBy('date')->get()
        ->mapWithKeys(fn (MeterDailyValue $value): array => [$value->date->toDateString() => round((float) $value->consumption, 4)])
        ->all();
}

it('spreads the consumption between two readings evenly over the days', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-01-01 00:00', 1000);
    recordReading($meter, '2026-01-11 00:00', 1100);

    $values = dailyValues($meter);

    expect($values)->toHaveCount(10)
        ->and($values['2026-01-01'])->toBe(10.0)
        ->and($values['2026-01-10'])->toBe(10.0)
        ->and(array_sum($values))->toBe(100.0)
        ->and(MeterDailyValue::query()->first()->organization_id)->toBe(defaultOrganization()->getKey());
});

it('recalculates only the affected days when a reading is inserted in between', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-01-01 00:00', 0);
    recordReading($meter, '2026-01-11 00:00', 100);
    recordReading($meter, '2026-01-06 00:00', 80);

    $values = dailyValues($meter);

    expect($values['2026-01-01'])->toBe(16.0)
        ->and($values['2026-01-05'])->toBe(16.0)
        ->and($values['2026-01-06'])->toBe(4.0)
        ->and(array_sum($values))->toBe(100.0);
});

it('splits a reading interval that starts and ends within a day proportionally', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-03-01 18:00', 0);
    recordReading($meter, '2026-03-02 06:00', 12);

    expect(dailyValues($meter))->toBe(['2026-03-01' => 6.0, '2026-03-02' => 6.0]);
});

it('converts the counter unit with the meter factor', function () {
    loginAsAdmin();
    $meter = Meter::factory()->medium(EnergyMedium::Gas)->create(['factor' => 10.5]);

    recordReading($meter, '2026-01-01', 100);
    recordReading($meter, '2026-01-02', 110);

    expect(dailyValues($meter))->toBe(['2026-01-01' => 105.0])
        ->and($meter->evaluationUnit())->toBe('kWh');
});

it('flags a decreasing reading and does not count negative consumption', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-01-01', 500);
    recordReading($meter, '2026-01-02', 400);

    $reading = $meter->readings()->latest('read_at')->first();

    expect($reading->status)->toBe(MeterReadingStatus::Suspicious)
        ->and($reading->flag)->toBe('decreased')
        ->and(dailyValues($meter))->toBe(['2026-01-01' => 0.0]);
});

it('flags a reading with an unusually high daily consumption', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();
    $recorder = app(MeterReadingRecorder::class);

    recordReading($meter, '2026-01-01', 0);
    recordReading($meter, '2026-01-31', 300);

    $check = $recorder->check($meter, CarbonImmutable::parse('2026-02-01'), 340);
    recordReading($meter, '2026-02-01', 340);

    expect($check->isSuspicious())->toBeTrue()
        ->and($check->flag)->toBe('unusually_high')
        ->and($check->actualDaily)->toBe(40.0)
        ->and($check->expectedDaily)->toBe(10.0)
        ->and($check->message('kWh'))->toContain('40,0')
        ->and($meter->readings()->latest('read_at')->first()->status)->toBe(MeterReadingStatus::Suspicious);

    recordReading($meter, '2026-02-02', 352);

    expect($meter->readings()->latest('read_at')->first()->status)->toBe(MeterReadingStatus::Plausible);
});

it('overwrites a reading at the same time instead of duplicating it', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-01-01', 0);
    recordReading($meter, '2026-01-03', 20);
    recordReading($meter, '2026-01-03', 40, MeterReadingSource::Import);

    expect($meter->readings()->count())->toBe(2)
        ->and($meter->readings()->latest('read_at')->first()->source)->toBe(MeterReadingSource::Import)
        ->and(array_sum(dailyValues($meter)))->toBe(40.0);
});

it('produces the same daily values regardless of the source', function () {
    loginAsAdmin();
    $manual = Meter::factory()->create();
    $api = Meter::factory()->create();

    foreach (['2026-04-01' => 10, '2026-04-08' => 80, '2026-04-30' => 300] as $date => $value) {
        recordReading($manual, $date, $value, MeterReadingSource::Manual);
        recordReading($api, $date, $value, MeterReadingSource::Api);
    }

    expect(dailyValues($manual))->toBe(dailyValues($api));
});

it('removes the days of a deleted reading', function () {
    loginAsAdmin();
    $meter = Meter::factory()->create();

    recordReading($meter, '2026-01-01', 0);
    recordReading($meter, '2026-01-03', 20);
    recordReading($meter, '2026-01-05', 40);

    app(MeterReadingRecorder::class)->delete($meter->readings()->latest('read_at')->first());

    expect(dailyValues($meter))->toBe(['2026-01-01' => 10.0, '2026-01-02' => 10.0]);
});

it('calculates virtual meters from their formula', function () {
    loginAsAdmin();
    $main = Meter::factory()->main()->create();
    $sub = Meter::factory()->childOf($main)->create();
    $rest = Meter::factory()->childOf($main)->create([
        'kind' => MeterKind::Virtual,
        'formula' => [['meter_id' => $main->getKey(), 'sign' => 1], ['meter_id' => $sub->getKey(), 'sign' => -1]],
    ]);

    recordReading($main, '2026-01-01', 0);
    recordReading($main, '2026-01-03', 100);
    recordReading($sub, '2026-01-01', 0);
    recordReading($sub, '2026-01-03', 30);

    expect(dailyValues($rest))->toBe(['2026-01-01' => 35.0, '2026-01-02' => 35.0]);

    app(ConsumptionAggregator::class)->refresh($rest);

    expect(dailyValues($rest))->toBe(['2026-01-01' => 35.0, '2026-01-02' => 35.0]);
    expect(fn () => recordReading($rest, '2026-01-04', 1))->toThrow(InvalidArgumentException::class);
});

it('reports monthly totals, consumption per asset and intensity without double counting', function () {
    loginAsAdmin();
    $property = Property::factory()->create(['net_area' => 1000]);
    $main = Meter::factory()->main()->create(['property_id' => $property->getKey()]);
    $sub = Meter::factory()->childOf($main)->create();
    $vent = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lüftung RLT-1']);
    $lift = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Aufzug A1']);
    $sub->measuredAssets()->attach([$vent->getKey() => ['share' => 70], $lift->getKey() => ['share' => 30]]);

    recordReading($main, '2026-01-01', 0);
    recordReading($main, '2026-03-01', 5900);
    recordReading($sub, '2026-01-01', 0);
    recordReading($sub, '2026-03-01', 1000);

    $report = app(EnergyReport::class);
    $months = $report->monthlyTotals($report->topLevelMeters(EnergyMedium::Electricity, $property), CarbonImmutable::parse('2026-01-01'), 3);
    $perAsset = $report->perAsset(Meter::query()->with('measuredAssets')->get(), CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-12-31'));

    expect($months)->toBe(['2026-01' => 3100.0, '2026-02' => 2800.0, '2026-03' => 0.0])
        ->and($perAsset->map(fn (array $row): array => [$row['asset']->name, round($row['consumption'], 2)])->all())
        ->toBe([['Lüftung RLT-1', 700.0], ['Aufzug A1', 300.0]])
        ->and(round($report->assetConsumption($lift, EnergyMedium::Electricity, CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-12-31')), 2))->toBe(300.0)
        ->and(round($report->intensity($property, EnergyMedium::Electricity, 2026), 2))->toBe(5.9);
});

it('builds an energy flow from meters to assets with an unassigned remainder', function () {
    loginAsAdmin();
    $property = Property::factory()->create();
    $main = Meter::factory()->main()->create(['property_id' => $property->getKey(), 'name' => 'Hauptzähler']);
    $sub = Meter::factory()->childOf($main)->create(['name' => 'UZ-04']);
    $vent = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lüftung']);
    $sub->measuredAssets()->attach($vent->getKey(), ['share' => 100]);

    recordReading($main, '2026-01-01', 0);
    recordReading($main, '2026-01-11', 1000);
    recordReading($sub, '2026-01-01', 0);
    recordReading($sub, '2026-01-11', 400);

    $flow = app(EnergyFlow::class)->build($property, EnergyMedium::Electricity, CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-01-31'));
    $links = collect($flow['links'])->map(fn (array $link): array => [$link['source'], $link['target'], round($link['value'], 2)])->all();

    expect(collect($flow['nodes'])->pluck('label', 'id')->all())->toMatchArray([
        $main->getKey() => 'Hauptzähler',
        $sub->getKey() => 'UZ-04',
        'asset:'.$vent->getKey() => 'Lüftung',
        EnergyFlow::UNASSIGNED => __('energy.flow.unassigned'),
    ])->and($links)->toContain(
        [$main->getKey(), $sub->getKey(), 400.0],
        [$sub->getKey(), 'asset:'.$vent->getKey(), 400.0],
        [$main->getKey(), EnergyFlow::UNASSIGNED, 600.0],
    );

    $layout = SankeyLayout::compute($flow);
    $heights = collect($layout['nodes'])->pluck('h', 'id');

    expect($layout['links'])->toHaveCount(3)
        ->and(round($heights[$main->getKey()] / $heights[$sub->getKey()], 1))->toBe(2.5);
});
