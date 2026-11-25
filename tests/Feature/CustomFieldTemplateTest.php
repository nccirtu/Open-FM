<?php

use App\Filament\Exports\AssetExporter;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Property;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Filament\Resources\CustomFieldSectionResource\Pages\ListCustomFieldSections;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Models\CustomFieldValue;
use Cafm\CustomFields\Support\CustomFieldTemplate;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

function fireExtinguisherTemplateArray(): array
{
    return [
        'custom_fields_template' => CustomFieldTemplate::VERSION,
        'entity' => 'asset',
        'key' => 'fire_extinguisher',
        'label' => 'Feuerlöscher',
        'translations' => ['en' => ['label' => 'Fire extinguisher']],
        'columns' => 2,
        'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => '@asset_type:Feuerlöscher']],
        'fields' => [
            ['type' => 'number', 'data' => ['key' => 'fuellmenge', 'label' => 'Füllmenge', 'unit' => 'kg', 'min' => 0, 'in_table' => true]],
            ['type' => 'checkbox_list', 'data' => ['key' => 'brandklassen', 'label' => 'Brandklassen', 'options' => [['label' => 'A', 'value' => 'A'], ['label' => 'B', 'value' => 'B']], 'columns' => 2, 'in_table' => true]],
            ['type' => 'date', 'data' => ['key' => 'naechste_druckpruefung', 'label' => 'Nächste Druckprüfung', 'in_table' => true]],
        ],
    ];
}

beforeEach(fn () => requiresPlugin('custom-fields'));

it('round-trips a custom field section through export and import', function () {
    $section = CustomFieldSection::query()->create([
        'entity' => 'asset',
        'key' => 'wartung_extra',
        'label' => 'Wartung Extra',
        'columns' => 2,
        'fields' => [
            ['type' => 'text', 'data' => ['key' => 'hinweis', 'label' => 'Hinweis']],
        ],
    ]);

    $exported = CustomFieldTemplate::export($section);

    expect($exported['entity'])->toBe('asset')
        ->and($exported['key'])->toBe('wartung_extra')
        ->and($exported['fields'][0]['data']['key'])->toBe('hinweis');

    $exported['key'] = 'wartung_extra_kopie';
    $imported = CustomFieldTemplate::import($exported);

    expect($imported->entity)->toBe('asset')
        ->and($imported->key)->toBe('wartung_extra_kopie')
        ->and($imported->fields)->toBe($section->fields);
});

it('rejects importing a template whose key already exists for the entity', function () {
    CustomFieldSection::query()->create(['entity' => 'asset', 'key' => 'fire_extinguisher', 'label' => 'Feuerlöscher', 'fields' => []]);

    expect(fn () => CustomFieldTemplate::import(fireExtinguisherTemplateArray()))
        ->toThrow(InvalidArgumentException::class);
});

it('imports a custom field template file via the artisan command, given an absolute path', function () {
    $path = tempnam(sys_get_temp_dir(), 'custom-fields-template-').'.json';
    file_put_contents($path, json_encode([
        'custom_fields_template' => 1,
        'entity' => 'asset',
        'key' => 'wartung_extra',
        'label' => 'Wartung Extra',
        'fields' => [
            ['type' => 'text', 'data' => ['key' => 'hinweis', 'label' => 'Hinweis']],
        ],
    ]));

    $this->artisan('custom-fields:import-template', ['file' => $path])->assertSuccessful();

    $section = CustomFieldSection::query()->where('entity', 'asset')->where('key', 'wartung_extra')->firstOrFail();

    expect($section->label)->toBe('Wartung Extra')
        ->and(collect($section->fieldSpecs())->pluck('data.key')->all())->toBe(['hinweis']);

    $this->artisan('custom-fields:import-template', ['file' => $path])->assertFailed();

    unlink($path);
});

it('shows the imported fire extinguisher fields only for that asset type and not elsewhere', function () {
    loginAsAdmin();
    CustomFieldTemplate::import(fireExtinguisherTemplateArray());
    CustomFields::flush();

    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);
    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
    $heating = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    $extinguisherAsset = Asset::create([
        'property_id' => $property->id, 'name' => 'FL 1', 'asset_type_id' => $fireExtinguisher->id,
    ]);
    $heatingAsset = Asset::create([
        'property_id' => $property->id, 'name' => 'Heizung 1', 'asset_type_id' => $heating->id,
    ]);
    CustomFieldValue::query()->create([
        'record_type' => Asset::class, 'record_id' => $extinguisherAsset->id,
        'data' => ['fire_extinguisher' => ['fuellmenge' => 6, 'brandklassen' => ['A', 'B'], 'naechste_druckpruefung' => '2033-01-01']],
    ]);

    Livewire::test(ViewAsset::class, ['record' => $extinguisherAsset->getRouteKey()])
        ->assertSee('Feuerlöscher')
        ->assertSee('Füllmenge')
        ->assertSee('6 kg');

    Livewire::test(ViewAsset::class, ['record' => $heatingAsset->getRouteKey()])
        ->assertDontSee('Füllmenge');
});

it('includes the imported fire extinguisher fields in the asset CSV export columns', function () {
    loginAsAdmin();
    CustomFieldTemplate::import(fireExtinguisherTemplateArray());
    CustomFields::flush();

    $columns = collect(AssetExporter::getColumns());
    $fuellmenge = $columns->first(fn ($column) => $column->getName() === 'customFieldValues.data.fire_extinguisher.fuellmenge');

    expect($fuellmenge)->not->toBeNull()
        ->and($fuellmenge->getLabel())->toBe('Füllmenge')
        ->and($columns->map(fn ($column) => $column->getName())->all())->toContain(
            'customFieldValues.data.fire_extinguisher.brandklassen',
            'customFieldValues.data.fire_extinguisher.naechste_druckpruefung',
        );
});

it('lets an admin import the template through the designer UI', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(ListCustomFieldSections::class)
        ->callAction('import', ['template' => UploadedFile::fake()->createWithContent('fire_extinguisher.json', json_encode(fireExtinguisherTemplateArray()))])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(CustomFieldSection::query()->where('entity', 'asset')->where('key', 'fire_extinguisher')->exists())->toBeTrue();
});
