<?php

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Enums\PropertyUsageType;
use App\Models\EnergyGoal;
use App\Models\Meter;
use App\Models\MeterDailyValue;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\Energy\EnergyDemoData;
use App\Services\Energy\EnergyFlow;
use Carbon\CarbonImmutable;

beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-03-15 10:00')));

it('creates a commercial and a residential demo property with meters, readings and goals', function () {
    loginAsAdmin();
    $other = Property::factory()->create(['property_number' => '4711']);

    $this->artisan('energy:demo', ['--organization' => defaultOrganization()->slug])->assertSuccessful();

    $office = Property::query()->where('property_number', 'DEMO-01')->firstOrFail();
    $home = Property::query()->where('property_number', 'DEMO-02')->firstOrFail();
    $sources = MeterReading::query()->distinct()->pluck('source')->map(fn (MeterReadingSource $source): string => $source->value)->sort()->values()->all();

    expect($office->usage_type)->toBe(PropertyUsageType::Commercial)
        ->and($home->usage_type)->toBe(PropertyUsageType::Residential)
        ->and(Meter::query()->count())->toBe(23)
        ->and($sources)->toBe(['api', 'import', 'manual'])
        ->and(MeterReading::query()->where('status', MeterReadingStatus::Suspicious->value)->pluck('flag')->sort()->values()->all())->toBe(['decreased', 'unusually_high'])
        ->and(MeterDailyValue::query()->whereDate('date', '2025-01-01')->exists())->toBeTrue()
        ->and(EnergyGoal::query()->where('notes', EnergyDemoData::NOTE)->count())->toBe(7)
        ->and(Meter::query()->value('organization_id'))->toBe(defaultOrganization()->getKey())
        ->and($other->fresh())->not->toBeNull();

    $main = Meter::query()->where('property_id', $office->getKey())->where('name', 'Hauptzähler Strom')->firstOrFail();
    $flow = app(EnergyFlow::class)->buildFor($main, CarbonImmutable::parse('2025-01-01'), CarbonImmutable::parse('2025-12-31'));

    expect(collect($flow['nodes'])->pluck('label')->all())->toContain('UZ Lüftung RLT 1', 'Allgemeinstrom (berechnet)', 'Beleuchtung Büroflächen', 'Kantinenküche');
});

it('replaces earlier demo data when run again and can remove it', function () {
    loginAsAdmin();

    $this->artisan('energy:demo')->assertSuccessful();
    $meters = Meter::query()->count();
    $readings = MeterReading::query()->count();

    $this->artisan('energy:demo')->assertSuccessful();

    expect(Meter::query()->count())->toBe($meters)
        ->and(MeterReading::query()->count())->toBe($readings)
        ->and(Property::query()->where('property_number', 'like', 'DEMO-%')->count())->toBe(2);

    $this->artisan('energy:demo', ['--remove' => true])->assertSuccessful();

    expect(Property::query()->where('property_number', 'like', 'DEMO-%')->exists())->toBeFalse()
        ->and(Meter::query()->exists())->toBeFalse()
        ->and(EnergyGoal::query()->exists())->toBeFalse();
});

it('fails for an unknown organization', function () {
    $this->artisan('energy:demo', ['--organization' => 'gibt-es-nicht'])->assertFailed();
});
