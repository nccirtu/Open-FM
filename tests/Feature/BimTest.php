<?php

use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Filament\Resources\PropertyResource\Pages\BimPage;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\BimModel;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Role;
use App\Models\StructureNode;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('imports floors and rooms from IFC data idempotently', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $bimModel = BimModel::factory()->create(['property_id' => $property->getKey()]);

    $payload = [
        'floors' => [
            ['guid' => 'GUID-EG', 'name' => 'Erdgeschoss', 'level' => 0],
            ['guid' => 'GUID-OG', 'name' => '1. Obergeschoss', 'level' => 1],
        ],
        'rooms' => [
            ['guid' => 'GUID-VK', 'name' => 'Verkaufsraum', 'storey_guid' => 'GUID-EG', 'area' => 412.35, 'height' => 3.2],
            ['guid' => 'GUID-LAGER', 'name' => 'Lager', 'storey_guid' => 'GUID-OG'],
            ['guid' => 'GUID-FREI', 'name' => 'Ohne Etage', 'storey_guid' => null],
        ],
        'assets' => [
            ['guid' => 'GUID-RLT', 'name' => 'RLT-Gerät Dach', 'ifc_class' => 'IFCUNITARYEQUIPMENT', 'storey_guid' => 'GUID-OG', 'room_guid' => 'GUID-LAGER'],
            ['guid' => 'GUID-WC', 'name' => 'Sanitärobjekt', 'ifc_class' => 'IFCSANITARYTERMINAL', 'storey_guid' => 'GUID-EG', 'room_guid' => null],
        ],
    ];

    $response = $this->postJson(route('bim-models.import', $bimModel), $payload);

    $response->assertOk()->assertJson(['floors' => 2, 'rooms' => 3, 'assets' => 2]);

    expect(StructureNode::query()->count())->toBe(5);
    expect(Asset::query()->count())->toBe(2);

    $verkaufsraum = StructureNode::query()->where('ifc_guid', 'GUID-VK')->firstOrFail();

    expect($verkaufsraum->parent->ifc_guid)->toBe('GUID-EG');
    expect($verkaufsraum->organization_id)->toBe(defaultOrganization()->getKey());
    expect($verkaufsraum->property_id)->toBe($property->getKey());
    expect((float) $verkaufsraum->area)->toBe(412.35);
    expect((float) $verkaufsraum->height)->toBe(3.2);

    $rlt = Asset::query()->where('ifc_guid', 'GUID-RLT')->firstOrFail();

    expect($rlt->name)->toBe('RLT-Gerät Dach');
    expect($rlt->property_id)->toBe($property->getKey());
    expect($rlt->structureNode->ifc_guid)->toBe('GUID-LAGER');
    expect($rlt->structureNode->parent->ifc_guid)->toBe('GUID-OG');
    expect($rlt->organization_id)->toBe(defaultOrganization()->getKey());

    $payload['rooms'][0]['name'] = 'Verkaufsraum EG (neu)';
    $payload['assets'][0]['name'] = 'RLT-Gerät Dach (neu)';

    $this->postJson(route('bim-models.import', $bimModel), $payload)->assertOk();

    expect(StructureNode::query()->count())->toBe(5);
    expect(Asset::query()->count())->toBe(2);
    expect(StructureNode::query()->where('ifc_guid', 'GUID-VK')->firstOrFail()->name)->toBe('Verkaufsraum EG (neu)');
    expect(Asset::query()->where('ifc_guid', 'GUID-RLT')->firstOrFail()->name)->toBe('RLT-Gerät Dach (neu)');
});

it('streams the IFC file and hides foreign models', function () {
    loginAsAdmin();

    Storage::fake('local');

    $bimModel = BimModel::factory()->create();
    Storage::disk('local')->put($bimModel->file_path, 'IFC-DATEN');

    $this->get(route('bim-models.file', $bimModel))->assertOk();

    $fremdeOrganisation = Organization::factory()->create();
    $fremdesModell = inOrganization($fremdeOrganisation, fn (): BimModel => BimModel::factory()->create());

    $this->get(route('bim-models.file', $fremdesModell))->assertNotFound();
});

it('renders the BIM page with empty state and with a model', function () {
    requiresPlugin('bim-viewer');

    loginAsAdminInFmPanel();

    $property = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->assertSuccessful()
        ->assertSee(__('property.bim.empty'));

    BimModel::factory()->create(['property_id' => $property->getKey(), 'name' => 'Halle-Nord.ifc']);

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->assertSuccessful()
        ->assertSee('Halle-Nord.ifc');
});

it('shows linked elements in the inspector drawer', function () {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    BimModel::factory()->create(['property_id' => $property->getKey()]);

    $floor = StructureNode::factory()->floor()->create(['property_id' => $property->getKey()]);
    $floor->forceFill(['ifc_guid' => 'GUID-EG'])->saveQuietly();

    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey(), 'name' => 'Lagerraum']);
    $room->forceFill(['ifc_guid' => 'GUID-LAGER'])->saveQuietly();

    $asset = Asset::factory()->create(['property_id' => $property->getKey(), 'name' => 'RLT-Gerät']);
    $asset->forceFill(['ifc_guid' => 'GUID-RLT'])->saveQuietly();

    $page = Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('inspectElement', 'GUID-RLT', 'RLT')
        ->assertSet('inspectedGuid', 'GUID-RLT')
        ->assertSet('inspectedName', 'RLT')
        ->assertActionMounted('inspectElement');

    $component = $page->instance();

    expect($component->findByGuid(Asset::class, 'GUID-RLT')?->name)->toBe('RLT-Gerät');
    expect($component->findByGuid(StructureNode::class, 'GUID-LAGER')?->name)->toBe('Lagerraum');
    expect($component->findByGuid(StructureNode::class, 'GUID-EG')?->name)->toBe('Erdgeschoss');
    expect($component->findByGuid(Asset::class, 'GUID-UNBEKANNT'))->toBeNull();

    $fremderProperty = Property::factory()->create();
    $fremdesAsset = Asset::factory()->create(['property_id' => $fremderProperty->getKey()]);
    $fremdesAsset->forceFill(['ifc_guid' => 'GUID-ANDERES-OBJEKT'])->saveQuietly();

    expect($component->findByGuid(Asset::class, 'GUID-ANDERES-OBJEKT'))->toBeNull();
});

it('creates an asset from an unlinked BIM element via the asset form modal', function () {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    BimModel::factory()->create(['property_id' => $property->getKey()]);
    $assetType = AssetType::query()->firstOrCreate(['name' => 'Wärmepumpe']);
    $anderesObjekt = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('inspectElement', 'GUID-NEU', 'Wärmepumpe Dach')
        ->mountAction('createAssetFromBim')
        ->assertActionMounted(['inspectElement', 'createAssetFromBim'])
        ->assertActionDataSet([
            'property_id' => $property->getKey(),
            'name' => 'Wärmepumpe Dach',
        ])
        ->setActionData([
            'property_id' => $anderesObjekt->getKey(),
            'name' => 'Wärmepumpe Nord',
            'asset_type_id' => $assetType->getKey(),
            'manufacturer' => 'Viessmann',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors()
        ->assertNotified(__('property.bim.inspector.asset_created'));

    $asset = Asset::query()->where('ifc_guid', 'GUID-NEU')->firstOrFail();

    expect($asset->name)->toBe('Wärmepumpe Nord');
    expect($asset->manufacturer)->toBe('Viessmann');
    expect($asset->asset_type_id)->toBe($assetType->getKey());
    expect($asset->property_id)->toBe($property->getKey());
    expect($asset->organization_id)->toBe(defaultOrganization()->getKey());

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('inspectElement', 'GUID-NEU', 'Wärmepumpe Dach')
        ->assertActionVisible('openAsset');

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->set('inspectedGuid', 'GUID-NEU')
        ->assertActionHidden('createAssetFromBim');
});

it('hides the BIM asset creation without the create permission', function () {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    BimModel::factory()->create(['property_id' => $property->getKey()]);

    Role::findByName('User')->revokePermissionTo('create_asset');

    $user = User::factory()->create();
    $user->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
    $user->assignRole('User');

    $this->actingAs($user);

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('inspectElement', 'GUID-NEU', 'Wärmepumpe Dach')
        ->assertActionHidden('createAssetFromBim');
});

it('validates the asset form before creating an asset from a BIM element', function () {
    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    BimModel::factory()->create(['property_id' => $property->getKey()]);

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->call('inspectElement', 'GUID-NEU', 'Wärmepumpe Dach')
        ->mountAction('createAssetFromBim')
        ->setActionData(['name' => null])
        ->callMountedAction()
        ->assertHasActionErrors(['name' => 'required', 'asset_type_id' => 'required']);

    expect(Asset::query()->where('ifc_guid', 'GUID-NEU')->exists())->toBeFalse();
});

it('opens the BIM drawer from assets and links to the full page', function () {
    requiresPlugin('bim-viewer');

    loginAsAdminInFmPanel();

    $property = Property::factory()->create();
    $bimModel = BimModel::factory()->create(['property_id' => $property->getKey()]);

    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    $asset->forceFill(['ifc_guid' => 'GUID-FOCUS'])->saveQuietly();

    $ohneGuid = Asset::factory()->create(['property_id' => $property->getKey()]);

    Livewire::test(ListAssets::class)
        ->assertActionVisible(TestAction::make('showInBim')->table($asset))
        ->assertActionHidden(TestAction::make('showInBim')->table($ohneGuid))
        ->mountAction(TestAction::make('showInBim')->table($asset))
        ->assertActionMounted(TestAction::make('showInBim')->table($asset));

    $html = view('filament.bim-drawer', ['bimModel' => $bimModel, 'guid' => 'GUID-FOCUS'])->render();

    expect($html)->toContain('bim-models');
    expect($html)->toContain($bimModel->getKey().'\/file');
    expect($html)->toContain('GUID-FOCUS');

    $leer = view('filament.bim-drawer', ['bimModel' => null, 'guid' => 'GUID-FOCUS'])->render();

    expect($leer)->toContain(e(__('property.bim.empty')));

    Livewire::withQueryParams(['highlight' => 'GUID-FOCUS'])
        ->test(BimPage::class, ['record' => $property->getKey()])
        ->assertSuccessful()
        ->assertSee('GUID-FOCUS');
});

it('creates a bim model via the upload action', function () {
    requiresPlugin('bim-viewer');

    loginAsAdminInFmPanel();

    Storage::fake('local');

    $property = Property::factory()->create();

    Livewire::test(BimPage::class, ['record' => $property->getKey()])
        ->callAction('upload', data: [
            'file' => UploadedFile::fake()->create('gebaeude.ifc', 512),
        ])
        ->assertHasNoActionErrors();

    $bimModel = BimModel::query()->firstOrFail();

    expect($bimModel->property_id)->toBe($property->getKey());
    expect($bimModel->organization_id)->toBe(defaultOrganization()->getKey());
    expect($bimModel->file_path)->toStartWith('bim-models/');
});
