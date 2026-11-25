<?php

use App\Enums\WasteIntervalUnit;
use App\Enums\WastePickupStatus;
use App\Enums\WasteScheduleSource;
use App\Models\WasteContainer;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Services\Waste\PickupGenerator;
use App\Services\Waste\ScheduleOccurrences;
use Carbon\CarbonImmutable;

/**
 * @return array<int, string>
 */
function occurrenceDates(WasteSchedule $schedule, string $from, string $until): array
{
    return collect(app(ScheduleOccurrences::class)->between($schedule, CarbonImmutable::parse($from), CarbonImmutable::parse($until)))
        ->map(fn (CarbonImmutable $date): string => $date->toDateString())
        ->all();
}

it('computes pickup days for each rule within the validity period', function (array $attributes, string $from, string $until, array $expected) {
    $schedule = new WasteSchedule([
        'source' => WasteScheduleSource::Rule,
        'interval_value' => 1,
        'interval_unit' => WasteIntervalUnit::Week,
        ...$attributes,
    ]);

    expect(occurrenceDates($schedule, $from, $until))->toBe($expected);
})->with([
    'every week on tuesday' => [['weekday' => 2, 'starts_on' => '2026-10-01'], '2026-10-01', '2026-10-20', ['2026-10-06', '2026-10-13', '2026-10-20']],
    'every second week, counted from the first tuesday' => [['weekday' => 2, 'interval_value' => 2, 'starts_on' => '2026-10-01'], '2026-10-10', '2026-11-05', ['2026-10-20', '2026-11-03']],
    'first monday of every month' => [['interval_unit' => WasteIntervalUnit::Month, 'weekday' => 1, 'week_of_month' => 1, 'starts_on' => '2026-09-15'], '2026-09-15', '2026-12-31', ['2026-10-05', '2026-11-02', '2026-12-07']],
    'last friday every second month' => [['interval_unit' => WasteIntervalUnit::Month, 'interval_value' => 2, 'weekday' => 5, 'week_of_month' => -1, 'starts_on' => '2026-10-01'], '2026-10-01', '2027-03-31', ['2026-10-30', '2026-12-25', '2027-02-26']],
    'yearly on the start date' => [['interval_unit' => WasteIntervalUnit::Year, 'starts_on' => '2026-03-15'], '2026-10-01', '2029-12-31', ['2027-03-15', '2028-03-15', '2029-03-15']],
    'once on the start date' => [['interval_unit' => WasteIntervalUnit::Once, 'starts_on' => '2026-10-21'], '2026-10-01', '2026-12-31', ['2026-10-21']],
    'valid until cuts off later days' => [['weekday' => 3, 'starts_on' => '2026-10-01', 'ends_on' => '2026-10-15'], '2026-10-01', '2026-12-31', ['2026-10-07', '2026-10-14']],
]);

it('takes imported days from the ICS calendar and none for on-demand plans', function () {
    $imported = new WasteSchedule([
        'source' => WasteScheduleSource::Ics,
        'starts_on' => '2026-10-01',
        'ics_dates' => ['2026-09-29', '2026-10-13', '2026-10-27', '2027-01-05'],
    ]);
    $onDemand = new WasteSchedule(['source' => WasteScheduleSource::OnDemand, 'starts_on' => '2026-10-01']);

    expect(occurrenceDates($imported, '2026-10-01', '2026-12-31'))->toBe(['2026-10-13', '2026-10-27'])
        ->and(occurrenceDates($onDemand, '2026-10-01', '2026-12-31'))->toBe([]);
});

it('generates pickups per container for the horizon and prunes outdated ones', function () {
    loginAsAdminInWastePanel();
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00'));

    $paper = WasteContainer::factory()->create(['number' => 'B-0101']);
    $second = WasteContainer::factory()->create(['number' => 'B-0103', 'property_id' => $paper->property_id, 'waste_fraction_id' => $paper->waste_fraction_id]);
    $schedule = WasteSchedule::factory()->create(['property_id' => $paper->property_id, 'weekday' => 2, 'starts_on' => '2026-10-01']);
    $schedule->containers()->attach([$paper->getKey(), $second->getKey()]);

    $generator = app(PickupGenerator::class);

    expect($generator->generate($schedule))->toBe(16)
        ->and($generator->generate($schedule))->toBe(0);

    $confirmed = WastePickup::query()->whereDate('due_on', '2026-10-27')->where('waste_container_id', $paper->getKey())->firstOrFail();
    $confirmed->forceFill(['status' => WastePickupStatus::Confirmed])->save();

    $schedule->update(['ends_on' => '2026-10-20']);
    $schedule->containers()->detach($second->getKey());
    $generator->generate($schedule);

    expect(WastePickup::query()->where('waste_container_id', $second->getKey())->exists())->toBeFalse()
        ->and(WastePickup::query()->where('waste_container_id', $paper->getKey())->orderBy('due_on')->pluck('due_on')->map->toDateString()->all())
        ->toBe(['2026-10-06', '2026-10-13', '2026-10-20', '2026-10-27']);
});
