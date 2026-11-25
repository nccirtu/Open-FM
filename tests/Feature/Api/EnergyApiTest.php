<?php

use App\Enums\EnergyMedium;
use App\Enums\MeterKind;
use App\Enums\MeterReadingSource;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Services\Energy\MeterReadingRecorder;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists and shows meters with filters and includes', function () {
    $property = Property::factory()->create();
    $main = Meter::factory()->main()->create(['property_id' => $property->getKey(), 'name' => 'Hauptzähler', 'number' => 'HZ-1']);
    Meter::factory()->medium(EnergyMedium::Water)->create(['name' => 'Wasser']);

    actingAsApiUser(['energy:read']);

    $this->getJson('/api/v1/meters')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/meters?filter[medium]=electricity')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $main->getKey());
    $this->getJson('/api/v1/meters?filter[number]=HZ-1&include=property')
        ->assertJsonPath('data.0.evaluation_unit', 'kWh')
        ->assertJsonPath('data.0.property.id', $property->getKey());
    $this->getJson('/api/v1/meters/'.$main->getKey().'?include=latestReading')->assertOk()->assertJsonPath('data.name', 'Hauptzähler');
});

it('stores readings in bulk through the same pipeline as the app', function () {
    $meter = Meter::factory()->create(['number' => 'UZ-1']);

    actingAsApiUser(['energy:write']);

    $this->postJson('/api/v1/meter-readings', [
        'readings' => [
            ['meter_id' => $meter->getKey(), 'read_at' => '2026-05-01T00:00:00+02:00', 'value' => 100],
            ['meter_number' => 'UZ-1', 'read_at' => '2026-05-11T00:00:00+02:00', 'value' => 200],
            ['meter_number' => 'UZ-1', 'read_at' => '2026-05-12T00:00:00+02:00', 'value' => 150],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('meta.stored', 3)
        ->assertJsonPath('meta.suspicious', 1)
        ->assertJsonPath('data.0.source', 'api');

    expect($meter->readings()->count())->toBe(3)
        ->and($meter->readings()->first()->source)->toBe(MeterReadingSource::Api)
        ->and(round((float) $meter->dailyValues()->sum('consumption')))->toBe(100.0);
});

it('rejects readings for unknown, foreign or calculated meters', function () {
    $virtual = Meter::factory()->create(['kind' => MeterKind::Virtual, 'number' => 'V-1']);
    $foreign = inOrganization(Organization::factory()->create(), fn (): Meter => Meter::factory()->create(['number' => 'FREMD']));

    actingAsApiUser(['energy:write']);

    $this->postJson('/api/v1/meter-readings', ['readings' => [['meter_number' => 'FREMD', 'read_at' => '2026-05-01', 'value' => 1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('readings.0.meter_number');
    $this->postJson('/api/v1/meter-readings', ['readings' => [['meter_id' => $virtual->getKey(), 'read_at' => '2026-05-01', 'value' => 1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('readings.0.meter_id');
    $this->postJson('/api/v1/meter-readings', ['readings' => [['read_at' => '2026-05-01', 'value' => -1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['readings.0.meter_id', 'readings.0.value']);

    expect($foreign->readings()->count())->toBe(0);
});

it('returns daily and monthly consumption of a meter', function () {
    $meter = Meter::factory()->create();
    $recorder = app(MeterReadingRecorder::class);
    $recorder->record($meter, CarbonImmutable::parse('2026-01-30'), 0, MeterReadingSource::Manual);
    $recorder->record($meter, CarbonImmutable::parse('2026-02-03'), 40, MeterReadingSource::Manual);

    actingAsApiUser(['energy:read']);

    $this->getJson('/api/v1/meters/'.$meter->getKey().'/consumption?from=2026-01-30&to=2026-02-03')
        ->assertOk()
        ->assertJsonPath('data.unit', 'kWh')
        ->assertJsonPath('data.total', 40)
        ->assertJsonPath('data.values.0', ['period' => '2026-01-30', 'consumption' => 10])
        ->assertJsonPath('data.values.4', ['period' => '2026-02-03', 'consumption' => null]);

    $this->getJson('/api/v1/meters/'.$meter->getKey().'/consumption?from=2026-01-01&to=2026-02-28&interval=month')
        ->assertJsonPath('data.values', [['period' => '2026-01', 'consumption' => 20], ['period' => '2026-02', 'consumption' => 20]]);
});

it('requires the energy abilities', function () {
    $meter = Meter::factory()->create();

    actingAsApiUser(['assets:read', 'assets:write']);

    $this->getJson('/api/v1/meters')->assertForbidden();
    $this->postJson('/api/v1/meter-readings', ['readings' => [['meter_id' => $meter->getKey(), 'read_at' => '2026-05-01', 'value' => 1]]])->assertForbidden();

    actingAsApiUser(['energy:read']);

    $this->postJson('/api/v1/meter-readings', ['readings' => [['meter_id' => $meter->getKey(), 'read_at' => '2026-05-01', 'value' => 1]]])->assertForbidden();
});
