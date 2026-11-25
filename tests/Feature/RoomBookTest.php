<?php

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Enums\EnergyRating;
use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Filament\Integrations\StructureTree\StructureTreeWidget;
use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\StructurePage;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use Livewire\Livewire;

it('stores area, location and energy data on a property', function () {
    loginAsAdmin();

    $property = Property::factory()->create();

    Livewire::test(EditProperty::class, ['record' => $property->getKey()])
        ->fillForm([
            'gross_area' => 1250.5,
            'cleanable_area' => 900,
            'latitude' => 50.9375,
            'longitude' => 6.9603,
            'energy_rating' => EnergyRating::C->value,
            'energy_cert_valid_until' => now()->addYears(5)->toDateString(),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $property->refresh();

    expect((float) $property->gross_area)->toBe(1250.5);
    expect($property->energy_rating)->toBe(EnergyRating::C);
    expect($property->energy_cert_valid_until)->not->toBeNull();
});

it('builds an arbitrarily deep structure tree per property', function () {
    loginAsAdmin();

    $property = Property::factory()->create();

    $gebaeude = StructureNode::factory()->building('Gebäude A')->create(['property_id' => $property->getKey()]);
    $etage = StructureNode::factory()->floor(0)->create(['property_id' => $property->getKey(), 'parent_id' => $gebaeude->getKey()]);
    $wohnung = StructureNode::factory()->create([
        'property_id' => $property->getKey(),
        'parent_id' => $etage->getKey(),
        'type' => StructureNodeType::Unit,
        'name' => 'Wohnung 1',
        'room_use' => null,
        'area' => null,
        'heated' => null,
    ]);
    $raum = StructureNode::factory()->create([
        'property_id' => $property->getKey(),
        'parent_id' => $wohnung->getKey(),
        'type' => StructureNodeType::Room,
        'name' => 'Bad',
        'room_use' => RoomUse::Sanitary,
        'area' => 8.5,
    ]);

    expect($raum->fullPath())->toBe('Gebäude A › Erdgeschoss › Wohnung 1 › Bad');
    expect($gebaeude->descendants()->count())->toBe(3);
    expect($raum->ancestors()->count())->toBe(3);
    expect($property->rootStructureNodes()->count())->toBe(1);

    $options = StructureNode::locationOptions($property->getKey());

    expect($options[$raum->getKey()])->toBe('Gebäude A › Erdgeschoss › Wohnung 1 › Bad');
    expect($options)->toHaveCount(4);
});

it('scopes structure nodes to the current tenant', function () {
    loginAsAdmin();

    $eigenerKnoten = StructureNode::factory()->create();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderKnoten = inOrganization($fremdeOrganisation, fn (): StructureNode => StructureNode::factory()->create());

    expect($fremderKnoten->organization_id)->toBe($fremdeOrganisation->getKey());
    expect($eigenerKnoten->organization_id)->toBe(defaultOrganization()->getKey());
});

it('creates an asset with structure location and lifecycle data', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $etage = StructureNode::factory()->floor(0)->create(['property_id' => $property->getKey()]);
    $raum = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $etage->getKey(), 'name' => 'Technikraum', 'room_use' => RoomUse::Technical]);
    $type = AssetType::query()->firstOrCreate(['name' => 'Klimaanlage']);

    Livewire::test(CreateAsset::class)
        ->fillForm([
            'property_id' => $property->getKey(),
            'name' => 'Klimagerät VK',
            'asset_type_id' => $type->getKey(),
            'structure_node_id' => $raum->getKey(),
            'condition' => AssetCondition::Good->value,
            'service_status' => AssetServiceStatus::InOperation->value,
            'warranty_until' => now()->addYear()->toDateString(),
            'purchase_price' => 4999.99,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->firstOrFail();

    expect($asset->structure_node_id)->toBe($raum->getKey());
    expect($asset->structureNode->fullPath())->toBe('Erdgeschoss › Technikraum');
    expect($asset->condition)->toBe(AssetCondition::Good);
    expect($asset->service_status)->toBe(AssetServiceStatus::InOperation);
    expect($asset->warrantyColor())->toBe('success');
});

it('inherits property and organization from the parent node', function () {
    loginAsAdmin();

    $gebaeude = StructureNode::factory()->building()->create();

    $etage = StructureNode::query()->create([
        'parent_id' => $gebaeude->getKey(),
        'type' => StructureNodeType::Floor,
        'name' => 'Erdgeschoss',
    ]);

    expect($etage->property_id)->toBe($gebaeude->property_id);
    expect($etage->organization_id)->toBe($gebaeude->organization_id);
});

it('deletes a subtree together with its parent node', function () {
    loginAsAdmin();

    $gebaeude = StructureNode::factory()->building()->create();
    $etage = StructureNode::factory()->floor()->create(['property_id' => $gebaeude->property_id, 'parent_id' => $gebaeude->getKey()]);
    StructureNode::factory()->create(['property_id' => $gebaeude->property_id, 'parent_id' => $etage->getKey()]);

    $gebaeude->delete();

    expect(StructureNode::query()->count())->toBe(0);
});

it('renders the structure tree page with sums and badges', function () {
    requiresPlugin('structure-tree');

    loginAsAdmin();

    $property = Property::factory()->create();
    $gebaeude = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);
    $etage = StructureNode::factory()->floor()->create(['property_id' => $property->getKey(), 'parent_id' => $gebaeude->getKey()]);
    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $etage->getKey(), 'name' => 'Lager', 'area' => 120.5]);
    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $etage->getKey(), 'name' => 'Büro', 'area' => 79.5]);

    Livewire::test(StructurePage::class, ['record' => $property->getKey()])
        ->assertSuccessful();

    Livewire::test(StructureTreeWidget::class, ['property' => $property])
        ->assertSuccessful()
        ->assertSee('Gebäude A')
        ->assertSee('Lager')
        ->assertSee('Σ 200,00 m²');
});

it('shows assets in the structure tree and relocates them via drag and drop', function () {
    requiresPlugin('structure-tree');

    loginAsAdmin();

    $property = Property::factory()->create();
    $etage = StructureNode::factory()->floor()->create(['property_id' => $property->getKey()]);
    $raum = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $etage->getKey(), 'name' => 'Technikraum']);

    $imRaum = Asset::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $raum->getKey(), 'name' => 'Klimagerät']);
    $ohneStandort = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Leiter']);

    $widget = Livewire::test(StructureTreeWidget::class, ['property' => $property]);

    $widget->assertSee('Klimagerät')
        ->assertSee('Leiter')
        ->assertSee('Ohne Standort');

    $widget->call('moveAttachment', 'asset-'.$ohneStandort->getKey(), $etage->getKey());

    expect($ohneStandort->refresh()->structure_node_id)->toBe($etage->getKey());

    $widget->call('moveAttachment', 'asset-'.$imRaum->getKey(), null);

    expect($imRaum->refresh()->structure_node_id)->toBeNull();

    $fremdesAsset = Asset::factory()->create(['name' => 'Fremd']);

    $widget->call('moveAttachment', 'asset-'.$fremdesAsset->getKey(), $etage->getKey());

    expect($fremdesAsset->refresh()->structure_node_id)->toBeNull();
});

it('nests component assets under their parent asset in the tree', function () {
    requiresPlugin('structure-tree');

    loginAsAdmin();

    $property = Property::factory()->create();
    $raum = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Technikraum']);

    $anlage = Asset::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $raum->getKey(), 'name' => 'RLT-Anlage']);
    $komponente = Asset::factory()->create(['property_id' => $property->getKey(), 'parent_asset_id' => $anlage->getKey(), 'name' => 'Ventilator']);
    $einzeln = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'Leiter']);

    $widget = Livewire::test(StructureTreeWidget::class, ['property' => $property]);

    $widget->assertSee('RLT-Anlage')->assertSee('Ventilator');

    $widget->call('moveAttachment', 'asset-'.$einzeln->getKey(), null, 'asset-'.$anlage->getKey());

    $einzeln->refresh();
    expect($einzeln->parent_asset_id)->toBe($anlage->getKey());
    expect($einzeln->structure_node_id)->toBe($raum->getKey());

    $widget->call('moveAttachment', 'asset-'.$anlage->getKey(), null, 'asset-'.$komponente->getKey());

    expect($anlage->refresh()->parent_asset_id)->toBeNull();

    $widget->call('moveAttachment', 'asset-'.$komponente->getKey(), $raum->getKey());

    $komponente->refresh();
    expect($komponente->parent_asset_id)->toBeNull();
    expect($komponente->structure_node_id)->toBe($raum->getKey());
});

it('manages structure nodes via the tree widget actions', function () {
    requiresPlugin('structure-tree');

    loginAsAdmin();

    $property = Property::factory()->create();
    $gebaeude = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);

    $widget = Livewire::test(StructureTreeWidget::class, ['property' => $property]);

    $widget->callAction('createNode', data: [
        'parent_id' => $gebaeude->getKey(),
        'name' => 'Erdgeschoss',
        'type' => StructureNodeType::Floor->value,
    ]);

    $etage = StructureNode::query()->where('name', 'Erdgeschoss')->firstOrFail();

    expect($etage->parent_id)->toBe($gebaeude->getKey());
    expect($etage->property_id)->toBe($property->getKey());
    expect($etage->organization_id)->toBe(defaultOrganization()->getKey());

    $widget->callAction('editNode', data: ['name' => '1. Obergeschoss'], arguments: ['node' => $etage->getKey()]);

    expect($etage->refresh()->name)->toBe('1. Obergeschoss');

    $widget->call('moveNode', $etage->getKey(), null, [$etage->getKey(), $gebaeude->getKey()]);

    expect($etage->refresh()->parent_id)->toBeNull();
    expect($etage->sort)->toBe(0);
    expect($gebaeude->refresh()->sort)->toBe(1);

    $widget->call('moveNode', $gebaeude->getKey(), $etage->getKey(), [$gebaeude->getKey()]);

    expect($gebaeude->refresh()->parent_id)->toBe($etage->getKey());

    $widget->call('moveNode', $etage->getKey(), $gebaeude->getKey(), [$etage->getKey()]);

    expect($etage->refresh()->parent_id)->toBeNull();

    $widget->callAction('deleteNode', arguments: ['node' => $etage->getKey()]);

    expect(StructureNode::query()->count())->toBe(0);
});

it('computes the warranty traffic light', function () {
    expect(Asset::query()->newModelInstance(['warranty_until' => now()->subDay()])->warrantyColor())->toBe('danger');
    expect(Asset::query()->newModelInstance(['warranty_until' => now()->addDays(30)])->warrantyColor())->toBe('warning');
    expect(Asset::query()->newModelInstance(['warranty_until' => now()->addYear()])->warrantyColor())->toBe('success');
    expect(Asset::query()->newModelInstance()->warrantyColor())->toBe('gray');
});

it('supports parent-child asset hierarchies', function () {
    loginAsAdmin();

    $anlage = Asset::factory()->create();
    $komponente = Asset::factory()->create([
        'property_id' => $anlage->property_id,
        'parent_asset_id' => $anlage->getKey(),
    ]);

    expect($komponente->parentAsset->getKey())->toBe($anlage->getKey());
    expect($anlage->childAssets()->count())->toBe(1);
});
