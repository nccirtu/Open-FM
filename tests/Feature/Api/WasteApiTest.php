<?php

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Enums\WastePickupKind;
use App\Enums\WastePickupStatus;
use App\Models\Organization;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Models\WasteContainer;
use App\Models\WasteFillReading;
use App\Models\WasteFraction;
use App\Models\WastePickup;
use App\Models\WasteSettings;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    $this->travelTo(CarbonImmutable::parse('2026-05-12 10:00'));
});

it('lists containers and pickups with filters and includes', function () {
    $paper = WasteFraction::factory()->create(['name' => 'Papier', 'code' => 'PPK']);
    $container = WasteContainer::factory()->create(['number' => 'B-1', 'waste_fraction_id' => $paper->getKey()]);
    WasteContainer::factory()->create(['number' => 'B-2']);
    WastePickup::factory()->forContainer($container)->create(['due_on' => '2026-05-14']);
    WastePickup::factory()->forContainer($container)->create(['due_on' => '2026-05-05', 'status' => WastePickupStatus::Missed]);

    actingAsApiUser(['waste:read']);

    $this->getJson('/api/v1/waste-containers')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/waste-containers?filter[number]=B-1&include=fraction')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.fraction.code', 'PPK');
    $this->getJson('/api/v1/waste-containers/'.$container->getKey())->assertOk()->assertJsonPath('data.number', 'B-1');
    $this->getJson('/api/v1/waste-pickups?filter[status]=missed')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.due_on', '2026-05-05');
    $this->getJson('/api/v1/waste-pickups?filter[from]=2026-05-10&include=container')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.container.number', 'B-1');
});

it('stores weighing data that confirms the planned pickup', function () {
    $container = WasteContainer::factory()->create(['number' => 'B-7']);
    $pickup = WastePickup::factory()->forContainer($container)->create(['due_on' => '2026-05-11']);

    actingAsApiUser(['waste:write']);

    $this->postJson('/api/v1/waste-collections', [
        'collections' => [
            ['container_number' => 'B-7', 'collected_at' => '2026-05-11T07:40:00+02:00', 'mass_kg' => 84.5, 'document_number' => 'WS-9'],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('meta.stored', 1)
        ->assertJsonPath('meta.confirmed_pickups', 1)
        ->assertJsonPath('data.0.mass_source', 'measured')
        ->assertJsonPath('data.0.source', 'api');

    expect($pickup->refresh()->status)->toBe(WastePickupStatus::Confirmed)
        ->and($pickup->confirmed_via)->toBe(WasteCollectionSource::Api)
        ->and($pickup->collection->document_number)->toBe('WS-9');
});

it('stores quantities per property and fraction without a container', function () {
    $property = Property::factory()->create();
    WasteFraction::factory()->create(['code' => 'GLS']);

    actingAsApiUser(['waste:write']);

    $this->postJson('/api/v1/waste-collections', [
        'collections' => [
            ['property_id' => $property->getKey(), 'fraction_code' => 'GLS', 'collected_at' => '2026-04-30', 'mass_kg' => 310],
        ],
    ])->assertCreated()->assertJsonPath('data.0.mass_kg', 310);

    expect(WasteCollection::query()->sole())
        ->property_id->toBe($property->getKey())
        ->mass_source->toBe(WasteMassSource::Measured);
});

it('rejects collections without a unique target or for foreign containers', function () {
    $property = Property::factory()->create();
    $container = WasteContainer::factory()->create();
    WasteFraction::factory()->create(['code' => 'GLS']);
    $foreign = inOrganization(Organization::factory()->create(), fn (): WasteContainer => WasteContainer::factory()->create(['number' => 'FREMD']));

    actingAsApiUser(['waste:write']);

    $this->postJson('/api/v1/waste-collections', ['collections' => [['container_number' => 'FREMD', 'collected_at' => '2026-05-01']]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('collections.0.container_number');
    $this->postJson('/api/v1/waste-collections', ['collections' => [['collected_at' => '2026-05-01', 'mass_kg' => 5]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('collections.0');
    $this->postJson('/api/v1/waste-collections', ['collections' => [[
        'container_id' => $container->getKey(),
        'property_id' => $property->getKey(),
        'fraction_code' => 'GLS',
        'collected_at' => '2026-05-01',
        'mass_kg' => 5,
    ]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('collections.0');

    expect(WasteCollection::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and($foreign->collections()->withoutGlobalScopes()->count())->toBe(0);
});

it('records sensor readings, detects the pickup and requests one above the threshold', function () {
    $container = WasteContainer::factory()->create(['number' => 'S-1', 'volume_l' => 1100]);
    $other = WasteContainer::factory()->create(['number' => 'S-2']);
    $pickup = WastePickup::factory()->forContainer($container)->create(['due_on' => '2026-05-12']);
    WasteSettings::forOrganization($container->organization_id)->fill(['sensor_request_pct' => 80])->save();

    actingAsApiUser(['waste:write']);

    $this->postJson('/api/v1/waste-fill-readings', [
        'readings' => [
            ['container_number' => 'S-1', 'measured_at' => '2026-05-12T06:00:00+02:00', 'fill_level_pct' => 70],
            ['container_number' => 'S-1', 'measured_at' => '2026-05-12T09:00:00+02:00', 'fill_level_pct' => 5],
            ['container_id' => $other->getKey(), 'measured_at' => '2026-05-12T09:00:00+02:00', 'fill_level_pct' => 92, 'temperature_c' => 21.5],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('meta.stored', 3)
        ->assertJsonPath('meta.pickups_detected', 1)
        ->assertJsonPath('meta.pickups_requested', 1);

    $collection = $pickup->refresh()->collection;

    expect($pickup->status)->toBe(WastePickupStatus::Confirmed)
        ->and($collection->source)->toBe(WasteCollectionSource::Sensor)
        ->and($collection->fill_level_pct)->toBe(70)
        ->and(WasteFillReading::query()->where('pickup_detected', true)->count())->toBe(1)
        ->and(WastePickup::query()->where('waste_container_id', $other->getKey())->sole())
        ->kind->toBe(WastePickupKind::OnDemand)
        ->status->toBe(WastePickupStatus::Requested);
});

it('requires the waste abilities', function () {
    $container = WasteContainer::factory()->create();

    actingAsApiUser(['energy:read', 'energy:write']);

    $this->getJson('/api/v1/waste-containers')->assertForbidden();

    actingAsApiUser(['waste:read']);

    $this->getJson('/api/v1/waste-collections')->assertOk();
    $this->postJson('/api/v1/waste-fill-readings', ['readings' => [['container_id' => $container->getKey(), 'measured_at' => '2026-05-01', 'fill_level_pct' => 40]]])->assertForbidden();
});

it('documents the waste endpoints in the OpenAPI description', function () {
    loginAsAdmin();

    $this->get('/docs/api.json')
        ->assertOk()
        ->assertJsonStructure(['paths' => ['/waste-containers', '/waste-containers/{wasteContainer}', '/waste-pickups', '/waste-collections', '/waste-fill-readings']]);
});
