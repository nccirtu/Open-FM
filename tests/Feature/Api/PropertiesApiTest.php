<?php

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists properties with filters, sorting and includes', function () {
    $berlin = Property::factory()->create(['property_number' => '1001', 'street' => 'Hauptstraße 1', 'city' => 'Berlin', 'country' => 'DE', 'energy_rating' => 'B']);
    $wien = Property::factory()->create(['property_number' => '1002', 'street' => 'Ringstraße 5', 'city' => 'Wien', 'country' => 'AT', 'energy_rating' => 'E']);
    StructureNode::factory()->building()->create(['property_id' => $berlin->getKey()]);
    Asset::factory()->create(['property_id' => $berlin->getKey()]);

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/properties')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.property_number', '1001')
        ->assertJsonStructure(['data' => [['id', 'property_number', 'street', 'city', 'energy_rating', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/properties?filter[search]=ring')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $wien->getKey());
    $this->getJson('/api/v1/properties?filter[search]=1001')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $berlin->getKey());
    $this->getJson('/api/v1/properties?filter[property_number]=1002')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $wien->getKey());
    $this->getJson('/api/v1/properties?filter[city]=berl')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/properties?filter[country]=AT')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/properties?filter[energy_rating]=E')->assertJsonCount(1, 'data')->assertJsonPath('data.0.energy_rating', 'E');
    $this->getJson('/api/v1/properties?sort=-city')->assertJsonPath('data.0.city', 'Wien');
    $this->getJson('/api/v1/properties?sort=city')->assertJsonPath('data.0.city', 'Berlin');

    $this->getJson('/api/v1/properties?include=structureNodes,assets&filter[property_number]=1001')
        ->assertOk()
        ->assertJsonCount(1, 'data.0.structure_nodes')
        ->assertJsonCount(1, 'data.0.assets');
});

it('shows a single property with requested relations', function () {
    $property = Property::factory()->create(['property_number' => '3000']);
    StructureNode::factory()->building('Halle')->create(['property_id' => $property->getKey()]);

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/properties/'.$property->getKey().'?include=structureNodes')
        ->assertOk()
        ->assertJsonPath('data.property_number', '3000')
        ->assertJsonPath('data.structure_nodes.0.name', 'Halle');

    $this->getJson('/api/v1/properties/'.$property->getKey().'?include=nope')->assertStatus(400);
});

it('hides properties of other organizations', function () {
    $property = Property::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/properties')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $property->getKey());
    $this->getJson('/api/v1/properties/'.$fremd->getKey())->assertNotFound();
});

it('creates and updates properties with tenant-scoped uniqueness', function () {
    Property::factory()->create(['property_number' => '4000']);
    inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create(['property_number' => '4001']));

    actingAsApiUser(['properties:write']);

    $this->postJson('/api/v1/properties', ['property_number' => '4000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_number']);

    $this->postJson('/api/v1/properties', ['property_number' => '4002', 'energy_rating' => 'Z'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['energy_rating']);

    $response = $this->postJson('/api/v1/properties', [
        'property_number' => '4001',
        'street' => 'Bahnhofstraße 2',
        'zip_code' => '80331',
        'city' => 'München',
        'country' => 'DE',
        'gross_area' => 1250.5,
        'latitude' => 48.137154,
        'longitude' => 11.576124,
        'energy_rating' => 'C',
        'energy_cert_valid_until' => '2030-06-30',
    ])->assertCreated()
        ->assertJsonPath('data.property_number', '4001')
        ->assertJsonPath('data.city', 'München')
        ->assertJsonPath('data.energy_rating', 'C')
        ->assertJsonPath('data.energy_cert_valid_until', '2030-06-30');

    $property = Property::query()->findOrFail($response->json('data.id'));

    expect($property->organization_id)->toBe(Filament::getTenant()->getKey());

    $this->patchJson('/api/v1/properties/'.$property->getKey(), ['property_number' => '4000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_number']);

    $this->patchJson('/api/v1/properties/'.$property->getKey(), ['city' => 'Augsburg', 'energy_rating' => null])
        ->assertOk()
        ->assertJsonPath('data.city', 'Augsburg')
        ->assertJsonPath('data.energy_rating', null)
        ->assertJsonPath('data.property_number', '4001');
});

it('deletes properties only when nothing depends on them', function () {
    $withAsset = Property::factory()->create();
    Asset::factory()->create(['property_id' => $withAsset->getKey()]);
    $withTicket = Property::factory()->create();
    Ticket::factory()->create(['property_id' => $withTicket->getKey()]);
    $withProject = Property::factory()->create();
    Project::factory()->create(['property_id' => $withProject->getKey()]);
    $empty = Property::factory()->create();

    actingAsApiUser(['properties:write']);

    $this->deleteJson('/api/v1/properties/'.$withAsset->getKey())->assertStatus(409)->assertJsonPath('message', __('api.errors.property_in_use'));
    $this->deleteJson('/api/v1/properties/'.$withTicket->getKey())->assertStatus(409);
    $this->deleteJson('/api/v1/properties/'.$withProject->getKey())->assertStatus(409);
    $this->deleteJson('/api/v1/properties/'.$empty->getKey())->assertNoContent();

    expect(Property::query()->find($empty->getKey()))->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $property = Property::factory()->create();

    actingAsApiUser(['properties:read']);

    $this->postJson('/api/v1/properties', ['property_number' => '5000'])->assertForbidden();
    $this->patchJson('/api/v1/properties/'.$property->getKey(), ['city' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/properties/'.$property->getKey())->assertForbidden();

    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/properties')->assertForbidden();
});
