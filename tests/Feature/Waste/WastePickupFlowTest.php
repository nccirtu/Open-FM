<?php

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Enums\WasteMissedReason;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\Company;
use App\Models\Property;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteDailyValue;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSchedule;
use App\Services\Waste\PickupMonitor;
use App\Services\Waste\PickupService;
use App\Services\Waste\WasteCollectionRecorder;
use Carbon\CarbonImmutable;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * @return array{manager: User, container: WasteContainer, pickup: WastePickup}
 */
function pickupFixture(): array
{
    $manager = User::factory()->create();
    $property = Property::factory()->managedBy($manager)->create(['property_number' => 'DEMO-01']);
    $fraction = WasteFraction::factory()->paper()->create();
    $container = WasteContainer::factory()->create([
        'property_id' => $property->getKey(),
        'waste_fraction_id' => $fraction->getKey(),
        'company_id' => Company::factory()->create(['name' => 'Muster Entsorgung GmbH'])->getKey(),
        'number' => 'B-0101',
        'volume_l' => 1100,
    ]);
    $pickup = WastePickup::factory()->forContainer($container)->create(['due_on' => '2026-10-07']);

    return ['manager' => $manager, 'container' => $container, 'pickup' => $pickup];
}

function dailyMass(WasteContainer $container, string $date): float
{
    return (float) WasteDailyValue::query()
        ->where('property_id', $container->property_id)
        ->where('waste_fraction_id', $container->waste_fraction_id)
        ->whereDate('date', $date)
        ->sum('mass_kg');
}

it('estimates the mass from volume, fill level and density unless a weight is given', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    $recorder = app(WasteCollectionRecorder::class);

    $estimated = $recorder->recordForContainer($container, CarbonImmutable::parse('2026-10-07 10:00'), WasteCollectionSource::Scan, fillLevelPct: 50);

    expect((float) $estimated->mass_kg)->toBe(33.0)
        ->and($estimated->mass_source)->toBe(WasteMassSource::Estimated)
        ->and($pickup->refresh()->status)->toBe(WastePickupStatus::Confirmed)
        ->and(dailyMass($container, '2026-10-07'))->toBe(33.0);

    $measured = $recorder->recordForContainer($container, CarbonImmutable::parse('2026-10-07 10:00'), WasteCollectionSource::Import, massKg: 41.5, pickup: $pickup);

    expect($measured->is($estimated))->toBeTrue()
        ->and($measured->mass_source)->toBe(WasteMassSource::Measured)
        ->and(dailyMass($container, '2026-10-07'))->toBe(41.5);
});

it('matches a collection to the nearest open pickup and records it unplanned otherwise', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    $recorder = app(WasteCollectionRecorder::class);

    $late = $recorder->recordForContainer($container, CarbonImmutable::parse('2026-10-09 07:30'), WasteCollectionSource::Sensor, massKg: 40);
    $unplanned = $recorder->recordForContainer($container, CarbonImmutable::parse('2026-10-20 07:30'), WasteCollectionSource::Sensor, massKg: 38);

    expect($late->waste_pickup_id)->toBe($pickup->getKey())
        ->and($pickup->refresh()->confirmed_via)->toBe(WasteCollectionSource::Sensor)
        ->and($unplanned->waste_pickup_id)->toBeNull();
});

it('completes a pickup without report at the cut-off as planned without notifying anyone', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    Notification::fake();
    $monitor = app(PickupMonitor::class);

    $monitor->run(CarbonImmutable::parse('2026-10-08 08:59'));

    expect($pickup->refresh()->status)->toBe(WastePickupStatus::Planned);

    $result = $monitor->run(CarbonImmutable::parse('2026-10-08 09:00'));
    $collection = WasteCollection::query()->where('waste_pickup_id', $pickup->getKey())->sole();

    expect($result['completed'])->toBe(1)
        ->and($pickup->refresh()->status)->toBe(WastePickupStatus::Confirmed)
        ->and($pickup->confirmed_via)->toBe(WasteCollectionSource::Schedule)
        ->and($pickup->isCompletedByPlan())->toBeTrue()
        ->and($collection->mass_source)->toBe(WasteMassSource::Estimated)
        ->and((float) $collection->mass_kg)->toBe(49.5)
        ->and(dailyMass($container, '2026-10-07'))->toBe(49.5)
        ->and(Ticket::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

it('replaces the planned quantity with a later report instead of counting it twice', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    app(PickupMonitor::class)->run(CarbonImmutable::parse('2026-10-09 09:00'));

    $weighed = app(WasteCollectionRecorder::class)->recordForContainer($container, CarbonImmutable::parse('2026-10-08 16:00'), WasteCollectionSource::Api, massKg: 52);

    expect($weighed->waste_pickup_id)->toBe($pickup->getKey())
        ->and($pickup->refresh()->confirmed_via)->toBe(WasteCollectionSource::Api)
        ->and($pickup->isCompletedByPlan())->toBeFalse()
        ->and(WasteCollection::query()->where('waste_pickup_id', $pickup->getKey())->count())->toBe(1)
        ->and(dailyMass($container, '2026-10-07') + dailyMass($container, '2026-10-08'))->toBe(52.0);
});

it('removes the planned quantity of a missed pickup and files a complaint with an extra pickup', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    app(PickupMonitor::class)->run(CarbonImmutable::parse('2026-10-08 09:00'));

    $extra = app(PickupService::class)->markMissed($pickup, WasteMissedReason::DisposerAbsent, 'Container stand voll an der Zufahrt.');

    expect($pickup->refresh()->status)->toBe(WastePickupStatus::Missed)
        ->and(WasteCollection::query()->where('waste_pickup_id', $pickup->getKey())->exists())->toBeFalse()
        ->and(dailyMass($container, '2026-10-07'))->toBe(0.0)
        ->and($pickup->ticket?->company_id)->toBe($container->company_id)
        ->and($extra?->kind)->toBe(WastePickupKind::Extra)
        ->and($extra?->status)->toBe(WastePickupStatus::Requested)
        ->and($extra?->free_of_charge)->toBeTrue();
});

it('files no complaint when the pickup failed for reasons on site', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();

    $extra = app(PickupService::class)->markMissed($pickup, WasteMissedReason::NotStaged);

    expect($extra)->toBeNull()
        ->and(Ticket::query()->where('company_id', $container->company_id)->exists())->toBeFalse();
});

it('reminds the caretaker the evening before a pickup that needs staging', function () {
    loginAsAdminInWastePanel();
    ['manager' => $manager, 'container' => $container, 'pickup' => $pickup] = pickupFixture();
    $schedule = WasteSchedule::factory()->create(['property_id' => $container->property_id, 'needs_staging' => true]);
    $pickup->update(['waste_schedule_id' => $schedule->getKey()]);
    Notification::fake();
    $monitor = app(PickupMonitor::class);

    $monitor->run(CarbonImmutable::parse('2026-10-06 17:59'));
    Notification::assertNothingSent();

    $monitor->run(CarbonImmutable::parse('2026-10-06 18:00'));
    $monitor->run(CarbonImmutable::parse('2026-10-06 19:00'));

    Notification::assertSentToTimes($manager, DatabaseNotification::class, 1);
    expect($pickup->refresh()->staging_reminded_at)->not->toBeNull();
});

it('converts assumed pickups of earlier versions into pickups completed by plan', function () {
    loginAsAdminInWastePanel();
    ['container' => $container, 'pickup' => $pickup] = pickupFixture();
    $collection = app(WasteCollectionRecorder::class)->recordForContainer($container, CarbonImmutable::parse('2026-10-07 12:00'), WasteCollectionSource::Schedule, fillLevelPct: 50, pickup: $pickup);
    DB::table('waste_pickups')->where('id', $pickup->getKey())->update(['status' => 'assumed', 'confirmed_via' => null]);
    DB::table('waste_collections')->where('id', $collection->getKey())->update(['mass_source' => 'assumed']);

    (require database_path('migrations/2026_10_12_155405_complete_assumed_waste_pickups_by_plan.php'))->up();

    expect($pickup->refresh()->isCompletedByPlan())->toBeTrue()
        ->and($collection->refresh()->mass_source)->toBe(WasteMassSource::Estimated);
});
