<?php

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists structure nodes with filters, sorting and includes', function () {
    $property = Property::factory()->create();
    $building = StructureNode::factory()->building('Gebäude A')->create(['property_id' => $property->getKey(), 'sort' => 1]);
    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $building->getKey(), 'name' => 'Lager', 'room_use' => 'storage', 'sort' => 2]);
    $other = StructureNode::factory()->create(['name' => 'Büro', 'room_use' => 'office', 'sort' => 3]);
    Asset::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $room->getKey()]);

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/structure-nodes')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.id', $building->getKey())
        ->assertJsonStructure(['data' => [['id', 'property_id', 'parent_id', 'type', 'name', 'sort', 'room_use', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/structure-nodes?filter[property_id]='.$property->getKey())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/structure-nodes?filter[parent_id]='.$building->getKey())->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $room->getKey());
    $this->getJson('/api/v1/structure-nodes?filter[type]=building')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/structure-nodes?filter[room_use]=office')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $other->getKey());
    $this->getJson('/api/v1/structure-nodes?filter[name]=lag')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/structure-nodes?filter[search]=bür')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/structure-nodes?sort=-name')->assertJsonPath('data.0.name', 'Lager');
    $this->getJson('/api/v1/structure-nodes?sort=name')->assertJsonPath('data.0.name', 'Büro');

    $this->getJson('/api/v1/structure-nodes?include=property,parent,children,assets&filter[type]=room&filter[property_id]='.$property->getKey())
        ->assertOk()
        ->assertJsonPath('data.0.property.id', $property->getKey())
        ->assertJsonPath('data.0.parent.id', $building->getKey())
        ->assertJsonCount(0, 'data.0.children')
        ->assertJsonCount(1, 'data.0.assets');
});

it('shows a single structure node with children', function () {
    $building = StructureNode::factory()->building()->create();
    StructureNode::factory()->floor()->create(['property_id' => $building->property_id, 'parent_id' => $building->getKey()]);

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/structure-nodes/'.$building->getKey().'?include=children')
        ->assertOk()
        ->assertJsonPath('data.type', 'building')
        ->assertJsonPath('data.children.0.type', 'floor');

    $this->getJson('/api/v1/structure-nodes/'.$building->getKey().'?include=nope')->assertStatus(400);
});

it('hides structure nodes of other organizations', function () {
    $node = StructureNode::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): StructureNode => StructureNode::factory()->create());

    actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/structure-nodes')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $node->getKey());
    $this->getJson('/api/v1/structure-nodes/'.$fremd->getKey())->assertNotFound();
});

it('creates structure nodes with tenant- and property-scoped parents', function () {
    $property = Property::factory()->create();
    $building = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);
    $otherProperty = Property::factory()->create();
    $fremdesObjekt = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());
    $fremderKnoten = inOrganization(Organization::factory()->create(), fn (): StructureNode => StructureNode::factory()->building()->create());

    actingAsApiUser(['properties:write']);

    $this->postJson('/api/v1/structure-nodes', ['property_id' => $fremdesObjekt->getKey(), 'type' => 'room', 'name' => 'Raum'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_id']);

    $this->postJson('/api/v1/structure-nodes', ['property_id' => $property->getKey(), 'parent_id' => $fremderKnoten->getKey(), 'type' => 'room', 'name' => 'Raum'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);

    $this->postJson('/api/v1/structure-nodes', ['property_id' => $otherProperty->getKey(), 'parent_id' => $building->getKey(), 'type' => 'room', 'name' => 'Raum'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);

    $this->postJson('/api/v1/structure-nodes', ['property_id' => $property->getKey(), 'type' => 'garage', 'name' => 'Raum'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);

    $response = $this->postJson('/api/v1/structure-nodes', [
        'property_id' => $property->getKey(),
        'parent_id' => $building->getKey(),
        'type' => 'room',
        'name' => 'Technikraum',
        'sort' => 5,
        'room_use' => 'technical',
        'area' => 32.5,
        'height' => 3.2,
        'heated' => false,
        'ifc_guid' => '2N1RfWzQ50Pvt8H_1GXYbC',
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Technikraum')
        ->assertJsonPath('data.parent_id', $building->getKey())
        ->assertJsonPath('data.room_use', 'technical')
        ->assertJsonPath('data.heated', false)
        ->assertJsonPath('data.ifc_guid', '2N1RfWzQ50Pvt8H_1GXYbC')
        ->assertJsonPath('data.property.id', $property->getKey());

    $node = StructureNode::query()->findOrFail($response->json('data.id'));

    expect($node->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($node->ifc_guid)->toBe('2N1RfWzQ50Pvt8H_1GXYbC');
});

it('updates structure nodes and rejects cyclic or self parents', function () {
    $property = Property::factory()->create();
    $building = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);
    $floor = StructureNode::factory()->floor()->create(['property_id' => $property->getKey(), 'parent_id' => $building->getKey()]);
    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey()]);
    $otherBuilding = StructureNode::factory()->building('Gebäude B')->create();

    actingAsApiUser(['properties:write']);

    $this->patchJson('/api/v1/structure-nodes/'.$floor->getKey(), ['parent_id' => $floor->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);

    $this->patchJson('/api/v1/structure-nodes/'.$building->getKey(), ['parent_id' => $room->getKey()])
        ->assertUnprocessable()
        ->assertJsonPath('errors.parent_id.0', __('api.validation.structure_node_parent_descendant'));

    $this->patchJson('/api/v1/structure-nodes/'.$room->getKey(), ['parent_id' => $otherBuilding->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);

    $this->patchJson('/api/v1/structure-nodes/'.$room->getKey(), ['name' => 'Verkauf', 'parent_id' => $building->getKey(), 'area' => 500, 'ifc_guid' => 'abc'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Verkauf')
        ->assertJsonPath('data.parent_id', $building->getKey())
        ->assertJsonPath('data.area', '500.00')
        ->assertJsonPath('data.ifc_guid', 'abc');
});

it('deletes structure nodes only without children and assets', function () {
    $building = StructureNode::factory()->building()->create();
    $floor = StructureNode::factory()->floor()->create(['property_id' => $building->property_id, 'parent_id' => $building->getKey()]);
    $roomWithAsset = StructureNode::factory()->create(['property_id' => $building->property_id, 'parent_id' => $floor->getKey()]);
    Asset::factory()->create(['property_id' => $building->property_id, 'structure_node_id' => $roomWithAsset->getKey()]);
    $emptyRoom = StructureNode::factory()->create(['property_id' => $building->property_id, 'parent_id' => $floor->getKey()]);

    actingAsApiUser(['properties:write']);

    $this->deleteJson('/api/v1/structure-nodes/'.$building->getKey())->assertStatus(409)->assertJsonPath('message', __('api.errors.structure_node_in_use'));
    $this->deleteJson('/api/v1/structure-nodes/'.$roomWithAsset->getKey())->assertStatus(409);
    $this->deleteJson('/api/v1/structure-nodes/'.$emptyRoom->getKey())->assertNoContent();

    expect(StructureNode::query()->find($emptyRoom->getKey()))->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $node = StructureNode::factory()->create();

    actingAsApiUser(['properties:read']);

    $this->postJson('/api/v1/structure-nodes', ['property_id' => $node->property_id, 'type' => 'room', 'name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/structure-nodes/'.$node->getKey(), ['name' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/structure-nodes/'.$node->getKey())->assertForbidden();
});
