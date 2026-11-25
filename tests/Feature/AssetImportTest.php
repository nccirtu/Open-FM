<?php

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Enums\StructureNodeType;
use App\Filament\Imports\AssetImporter;
use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Support\Plugins\PluginManager;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Models\CustomFieldSection;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Testing\TestAction;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * @var array<string, string>
 */
const ASSET_IMPORT_COLUMN_MAP = [
    'asset_number' => 'Asset-Nr.',
    'name' => 'Bezeichnung',
    'property' => 'Objekt',
    'location' => 'Standort',
    'asset_type' => 'Typ',
    'manufacturer' => 'Hersteller',
    'maintenance_partner' => 'Wartungspartner',
    'condition' => 'Zustand',
    'service_status' => 'Betriebsstatus',
    'installed_at' => 'Installiert am',
    'next_maintenance_at' => 'Nächste Wartung',
    'purchase_price' => 'Kaufpreis',
];

/**
 * @param  array<string, string>  $row
 * @param  array<string, mixed>  $options
 */
function importAssetRow(array $row, array $options = []): AssetImporter
{
    $columnMap = array_filter(ASSET_IMPORT_COLUMN_MAP, fn (string $header): bool => array_key_exists($header, $row));
    $importer = new AssetImporter(new Import, $columnMap, ['organization_id' => defaultOrganization()->getKey(), ...$options]);

    $importer($row);

    return $importer;
}

function importFixtures(): array
{
    $property = Property::factory()->create(['property_number' => '1001']);
    $building = StructureNode::factory()->building('Gebäude A')->create(['property_id' => $property->getKey()]);
    $floor = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $building->getKey(), 'type' => StructureNodeType::Floor, 'name' => 'EG']);
    $corridor = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey(), 'type' => StructureNodeType::Room, 'name' => 'Flur']);
    $type = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);

    return compact('property', 'building', 'floor', 'corridor', 'type');
}

it('imports an asset with readable references, German dates, numbers and labels', function () {
    loginAsAdmin();
    ['property' => $property, 'corridor' => $corridor, 'type' => $type] = importFixtures();
    $company = Company::factory()->create(['name' => 'Brandschutz Müller']);

    $asset = importAssetRow([
        'Bezeichnung' => 'Feuerlöscher EG Flur',
        'Objekt' => '1001',
        'Standort' => 'Gebäude A › EG › Flur',
        'Typ' => 'feuerlöscher',
        'Hersteller' => 'Minimax',
        'Wartungspartner' => 'brandschutz müller',
        'Zustand' => 'Gut',
        'Betriebsstatus' => 'In Betrieb',
        'Installiert am' => '1.3.2021',
        'Nächste Wartung' => '15.03.2027',
        'Kaufpreis' => '1.234,50 €',
    ])->getRecord();

    expect($asset->fresh())
        ->organization_id->toBe(defaultOrganization()->getKey())
        ->property_id->toBe($property->getKey())
        ->structure_node_id->toBe($corridor->getKey())
        ->asset_type_id->toBe($type->getKey())
        ->company_id->toBe($company->getKey())
        ->condition->toBe(AssetCondition::Good)
        ->service_status->toBe(AssetServiceStatus::InOperation)
        ->purchase_price->toBe('1234.50')
        ->asset_number->toBe(1)
        ->and($asset->installed_at->toDateString())->toBe('2021-03-01')
        ->and($asset->next_maintenance_at->toDateString())->toBe('2027-03-15');
});

it('resolves a unique location by name and rejects ambiguous ones', function () {
    loginAsAdmin();
    ['property' => $property, 'floor' => $floor, 'corridor' => $corridor] = importFixtures();

    expect(importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '1001', 'Standort' => 'flur', 'Typ' => 'Feuerlöscher'])->getRecord()->structure_node_id)
        ->toBe($corridor->getKey());

    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey(), 'name' => 'Flur']);

    expect(fn () => importAssetRow(['Bezeichnung' => 'B', 'Objekt' => '1001', 'Standort' => 'Flur', 'Typ' => 'Feuerlöscher']))
        ->toThrow(RowImportFailedException::class, __('assets.import.errors.location_ambiguous', ['value' => 'Flur']));
});

it('fails rows with unknown references instead of guessing', function () {
    loginAsAdmin();
    importFixtures();

    expect(fn () => importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '9999', 'Typ' => 'Feuerlöscher']))
        ->toThrow(RowImportFailedException::class, __('assets.import.errors.property_not_found', ['value' => '9999']))
        ->and(fn () => importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '1001', 'Typ' => 'Raumschiff']))
        ->toThrow(RowImportFailedException::class, __('assets.import.errors.asset_type_not_found', ['value' => 'Raumschiff']))
        ->and(fn () => importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '1001', 'Typ' => 'Feuerlöscher', 'Wartungspartner' => 'Unbekannt GmbH']))
        ->toThrow(RowImportFailedException::class, __('assets.import.errors.company_not_found', ['value' => 'Unbekannt GmbH']))
        ->and(fn () => importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '1001', 'Typ' => 'Feuerlöscher', 'Installiert am' => '31.02.2021']))
        ->toThrow(ValidationException::class);

    expect(Asset::query()->count())->toBe(0);
});

it('creates missing asset types and companies when asked to', function () {
    loginAsAdmin();
    importFixtures();

    $asset = importAssetRow(
        ['Bezeichnung' => 'Lüftung Dach', 'Objekt' => '1001', 'Typ' => 'Dachventilator', 'Wartungspartner' => 'Luft & Klima KG'],
        ['createMissing' => true],
    )->getRecord();

    expect($asset->assetType->name)->toBe('Dachventilator')
        ->and($asset->company->name)->toBe('Luft & Klima KG')
        ->and($asset->company->organization_id)->toBe(defaultOrganization()->getKey());
});

it('only looks up references of the importing organization', function () {
    loginAsAdmin();
    importFixtures();
    $foreign = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create(['property_number' => '2002']));

    expect(fn () => importAssetRow(['Bezeichnung' => 'A', 'Objekt' => '2002', 'Typ' => 'Feuerlöscher']))
        ->toThrow(RowImportFailedException::class);

    expect($foreign->assets()->count())->toBe(0);
});

it('updates existing assets by asset number and keeps values of blank cells', function () {
    loginAsAdmin();
    importFixtures();
    $asset = importAssetRow(['Bezeichnung' => 'Alt', 'Objekt' => '1001', 'Typ' => 'Feuerlöscher', 'Hersteller' => 'Minimax'])->getRecord();

    $updated = importAssetRow(
        ['Asset-Nr.' => (string) $asset->asset_number, 'Bezeichnung' => 'Neu', 'Hersteller' => '', 'Zustand' => 'schlecht'],
        ['updateExisting' => true],
    )->getRecord();

    expect($updated->is($asset))->toBeTrue()
        ->and($asset->fresh())
        ->name->toBe('Neu')
        ->manufacturer->toBe('Minimax')
        ->condition->toBe(AssetCondition::Poor)
        ->and(Asset::query()->count())->toBe(1);
});

it('reads the type-specific custom fields of each row', function () {
    requiresPlugin('custom-fields');
    loginAsAdmin();
    importFixtures();
    $lift = AssetType::query()->firstOrCreate(['name' => 'Aufzug']);
    importCustomFieldSection('feuerloescher', 'Feuerlöscher', [
        ['type' => 'number', 'data' => ['key' => 'fuellmenge', 'label' => 'Füllmenge', 'unit' => 'kg', 'translations' => ['en' => ['label' => 'Fill quantity']]]],
        ['type' => 'checkbox_list', 'data' => ['key' => 'brandklassen', 'label' => 'Brandklassen', 'options' => [['label' => 'A', 'value' => 'A'], ['label' => 'B', 'value' => 'B'], ['label' => 'C', 'value' => 'C']]]],
        ['type' => 'select', 'data' => ['key' => 'bauart', 'label' => 'Bauart', 'options' => [['label' => 'Aufladelöscher'], ['label' => 'Dauerdrucklöscher']]]],
        ['type' => 'toggle', 'data' => ['key' => 'plakette', 'label' => 'Prüfplakette vorhanden']],
        ['type' => 'date', 'data' => ['key' => 'naechste_druckpruefung', 'label' => 'Nächste Druckprüfung']],
    ]);
    importCustomFieldSection('aufzug', 'Aufzug', [
        ['type' => 'number', 'data' => ['key' => 'tragfaehigkeit', 'label' => 'Tragfähigkeit', 'unit' => 'kg']],
    ]);

    $extinguisher = importAssetRow([
        'Bezeichnung' => 'Löscher 1',
        'Objekt' => '1001',
        'Typ' => 'Feuerlöscher',
        'Füllmenge' => '6,5',
        'Feuerlöscher: Brandklassen' => 'A, B',
        'Bauart' => 'dauerdrucklöscher',
        'Prüfplakette vorhanden' => 'ja',
        'Nächste Druckprüfung' => '01.06.2030',
        'Tragfähigkeit' => '630',
    ])->getRecord();

    $elevator = importAssetRow([
        'Bezeichnung' => 'Personenaufzug',
        'Objekt' => '1001',
        'Typ' => 'Aufzug',
        'Füllmenge' => '6,5',
        'Tragfähigkeit' => '1.000',
    ])->getRecord();

    expect($extinguisher->fresh()->customFieldValues->data)->toBe([
        'feuerloescher' => [
            'fuellmenge' => 6.5,
            'brandklassen' => ['A', 'B'],
            'bauart' => 'Dauerdrucklöscher',
            'plakette' => true,
            'naechste_druckpruefung' => '2030-06-01',
        ],
    ])->and($elevator->fresh()->customFieldValues->data)->toBe([
        'aufzug' => ['tragfaehigkeit' => 1000],
    ]);

    expect(fn () => importAssetRow(['Bezeichnung' => 'Löscher 2', 'Objekt' => '1001', 'Typ' => 'Feuerlöscher', 'Füllmenge' => 'viel']))
        ->toThrow(RowImportFailedException::class, 'Füllmenge: '.__('assets.import.errors.invalid_number', ['value' => 'viel']));

    expect(Asset::query()->where('name', 'Löscher 2')->exists())->toBeFalse()
        ->and($lift->assets()->count())->toBe(1);
});

it('builds an import template with the fields of the selected asset types', function () {
    loginAsAdmin();
    ['type' => $type] = importFixtures();

    $csv = AssetImporter::templateCsv([$type->getKey()]);
    $lines = array_map(fn (string $line): array => str_getcsv($line, ';'), explode("\n", trim(ltrim($csv, "\u{FEFF}"))));

    expect($lines[0])->toContain(__('assets.fields.name'), __('property.model'), __('assets.fields.type'))
        ->and($lines[1])->toContain('Feuerlöscher', '1001');

    if (app(PluginManager::class)->installed('custom-fields')) {
        importCustomFieldSection('feuerloescher', 'Feuerlöscher', [
            ['type' => 'number', 'data' => ['key' => 'fuellmenge', 'label' => 'Füllmenge']],
        ]);

        expect(AssetImporter::templateCsv([$type->getKey()]))->toContain('Feuerlöscher: Füllmenge');
    }

    Livewire::test(ListAssets::class)
        ->callAction(TestAction::make('importTemplate')->table(), data: ['asset_type_ids' => [$type->getKey()]])
        ->assertFileDownloaded('asset-import-vorlage.csv');
});

it('offers the import action to users who may create assets', function () {
    loginAsAdmin();

    Livewire::test(ListAssets::class)
        ->assertActionVisible(TestAction::make('import')->table())
        ->assertActionVisible(TestAction::make('importTemplate')->table());
});

/**
 * @param  array<int, array<string, mixed>>  $fields
 */
function importCustomFieldSection(string $key, string $assetType, array $fields): CustomFieldSection
{
    AssetType::query()->firstOrCreate(['name' => $assetType]);

    $section = CustomFieldSection::query()->create([
        'entity' => 'asset',
        'key' => $key,
        'label' => $assetType,
        'columns' => 2,
        'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => '@asset_type:'.$assetType]],
        'fields' => $fields,
    ]);

    CustomFields::flush();

    return $section;
}
