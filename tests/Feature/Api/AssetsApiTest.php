<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists assets with pagination, filters, sorting and includes', function () {
    $property = Property::factory()->create(['property_number' => '2000']);
    $type = AssetType::query()->firstOrCreate(['name' => 'Aufzug']);
    $treffer = Asset::factory()->create(['name' => 'Personenaufzug', 'manufacturer' => 'Schindler', 'property_id' => $property->getKey(), 'asset_type_id' => $type->getKey(), 'condition' => 'poor']);
    Asset::factory()->create(['name' => 'Heizkessel', 'manufacturer' => 'Vaillant']);

    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'asset_number', 'name', 'manufacturer', 'condition', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/assets?filter[search]=schind')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/assets?filter[property_number]=2000')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/assets?filter[type]=Aufzug')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/assets?filter[condition]=poor')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/assets?filter[asset_type_id]='.$type->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/assets?sort=name')->assertJsonPath('data.0.name', 'Heizkessel');
    $this->getJson('/api/v1/assets?sort=-name')->assertJsonPath('data.0.name', 'Personenaufzug');

    $this->getJson('/api/v1/assets?include=property,assetType&filter[name]=Personen')
        ->assertOk()
        ->assertJsonPath('data.0.property.property_number', '2000')
        ->assertJsonPath('data.0.asset_type.name', 'Aufzug');
});

it('shows a single asset with requested relations', function () {
    $company = Company::factory()->create(['name' => 'Service GmbH']);
    $asset = Asset::factory()->create(['company_id' => $company->getKey()]);

    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets/'.$asset->getKey().'?include=company')
        ->assertOk()
        ->assertJsonPath('data.id', $asset->getKey())
        ->assertJsonPath('data.company.name', 'Service GmbH');

    $this->getJson('/api/v1/assets/'.$asset->getKey().'?include=nope')->assertStatus(400);
});

it('hides assets of other organizations', function () {
    $asset = Asset::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Asset => Asset::factory()->create());

    actingAsApiUser(['assets:read']);

    $this->getJson('/api/v1/assets')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $asset->getKey());
    $this->getJson('/api/v1/assets/'.$fremd->getKey())->assertNotFound();
});

it('creates, updates and deletes assets with tenant-scoped validation', function () {
    $property = Property::factory()->create();
    $node = StructureNode::query()->create(['property_id' => $property->getKey(), 'type' => 'room', 'name' => 'Technik']);
    $type = AssetType::query()->firstOrCreate(['name' => 'Heizung']);
    $fremdesObjekt = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());

    actingAsApiUser(['assets:write']);

    $this->postJson('/api/v1/assets', ['name' => 'Kessel', 'property_id' => $fremdesObjekt->getKey(), 'asset_type_id' => $type->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_id']);

    $response = $this->postJson('/api/v1/assets', [
        'name' => 'Kessel',
        'property_id' => $property->getKey(),
        'structure_node_id' => $node->getKey(),
        'asset_type_id' => $type->getKey(),
        'manufacturer' => 'Viessmann',
        'condition' => 'good',
        'installed_at' => '2024-05-01',
        'purchase_price' => 12500.5,
    ])->assertCreated()
        ->assertJsonPath('data.name', 'Kessel')
        ->assertJsonPath('data.condition', 'good')
        ->assertJsonPath('data.property.id', $property->getKey());

    $asset = Asset::query()->findOrFail($response->json('data.id'));

    expect($asset->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($asset->asset_number)->toBe(1);

    $this->patchJson('/api/v1/assets/'.$asset->getKey(), ['manufacturer' => 'Buderus', 'parent_asset_id' => $asset->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_asset_id']);

    $this->patchJson('/api/v1/assets/'.$asset->getKey(), ['manufacturer' => 'Buderus', 'service_status' => 'in_storage'])
        ->assertOk()
        ->assertJsonPath('data.manufacturer', 'Buderus')
        ->assertJsonPath('data.service_status', 'in_storage');

    $this->deleteJson('/api/v1/assets/'.$asset->getKey())->assertNoContent();

    expect(Asset::query()->find($asset->getKey()))->toBeNull();
});

it('manages asset types and refuses to delete types in use', function () {
    actingAsApiUser(['assets:write'])->forceFill(['is_super_admin' => true])->save();

    $created = $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertCreated()->json('data');

    $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertUnprocessable();

    $this->patchJson('/api/v1/asset-types/'.$created['id'], ['name' => 'Umwälzpumpe'])->assertOk()->assertJsonPath('data.name', 'Umwälzpumpe');

    $this->getJson('/api/v1/asset-types?filter[name]=umwälz')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.assets_count', 0);

    Asset::factory()->create(['asset_type_id' => $created['id']]);

    $this->deleteJson('/api/v1/asset-types/'.$created['id'])->assertStatus(409);

    $unused = $this->postJson('/api/v1/asset-types', ['name' => 'Kompressor'])->json('data.id');

    $this->deleteJson('/api/v1/asset-types/'.$unused)->assertNoContent();
});
