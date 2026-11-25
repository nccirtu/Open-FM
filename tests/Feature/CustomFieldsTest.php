<?php

use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Organization;
use App\Models\Property;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Filament\Resources\CustomFieldSectionResource;
use Cafm\CustomFields\Filament\Resources\CustomFieldSectionResource\Pages\CreateCustomFieldSection;
use Cafm\CustomFields\Filament\Resources\CustomFieldSectionResource\Pages\EditCustomFieldSection;
use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Models\CustomFieldValue;
use Cafm\CustomFields\Support\ConditionResolver;
use Cafm\FormBuilder\Schema\DesignerSchema;
use Cafm\FormBuilder\Support\Translatable;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $overrides
 */
function fireExtinguisherSection(array $overrides = []): CustomFieldSection
{
    $tenantIds = $overrides['tenants'] ?? [defaultOrganization()->getKey()];
    unset($overrides['tenants']);
    fireExtinguisherType();

    $section = CustomFieldSection::query()->create(array_merge([
        'entity' => 'asset',
        'key' => 'fire_extinguisher',
        'label' => 'Feuerlöscher',
        'translations' => ['en' => ['label' => 'Fire extinguisher']],
        'columns' => 2,
        'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => '@asset_type:Feuerlöscher']],
        'fields' => [
            ['type' => 'number', 'data' => ['key' => 'kg', 'label' => 'Kilogramm', 'unit' => 'kg', 'in_table' => true, 'translations' => ['en' => ['label' => 'Kilograms']]]],
            ['type' => 'toggle', 'data' => ['key' => 'plakette', 'label' => 'Prüfplakette vorhanden', 'in_table' => true]],
            ['type' => 'text', 'data' => ['key' => 'plakette_hinweis', 'label' => 'Hinweis', 'visible_when' => [['field' => 'plakette', 'operator' => 'equals', 'value' => '1']]]],
            ['type' => 'select', 'data' => ['key' => 'klasse', 'label' => 'Brandklasse', 'options' => [['label' => 'A'], ['label' => 'B', 'value' => 'b']], 'in_table' => true]],
        ],
    ], $overrides));

    $section->tenants()->sync($tenantIds);
    CustomFields::flush();

    return $section->fresh();
}

function fireExtinguisherType(): AssetType
{
    return AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
}

function assetOfType(AssetType $type, string $name = 'Löscher 1'): Asset
{
    $property = Property::query()->firstOr(fn () => Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']));

    return Asset::create([
        'property_id' => $property->id,
        'name' => $name,
        'asset_type_id' => $type->id,
    ]);
}

beforeEach(function () {
    requiresPlugin('custom-fields');

    CustomFields::flush();
});

it('attaches the values relationship to the configured models without touching them', function () {
    expect((new Asset)->isRelation(CustomFields::relationshipName()))->toBeTrue()
        ->and(CustomFields::entities())->toContain('asset', 'property', 'ticket')
        ->and(CustomFields::entityKeyForModel(Asset::class))->toBe('asset');
});

it('renders nothing when no sections are defined', function () {
    loginAsAdmin();

    expect(CustomFields::formComponents('asset'))->toBe([])
        ->and(CustomFields::infolistComponents('asset'))->toBe([])
        ->and(CustomFields::tableColumns('asset'))->toBe([]);
});

it('shows the section in the asset form only for the configured asset type and saves the values', function () {
    loginAsAdmin();
    fireExtinguisherSection();

    $heizung = AssetType::query()->firstOrCreate(['name' => 'Heizung']);
    $property = Property::create(['property_number' => '12345', 'city' => 'Berlin', 'country' => 'DE']);

    Livewire::test(CreateAsset::class)
        ->fillForm(['asset_type_id' => $heizung->id])
        ->assertFormFieldIsHidden('customFieldValues.data.fire_extinguisher.kg')
        ->fillForm(['asset_type_id' => fireExtinguisherType()->id])
        ->assertFormFieldIsVisible('customFieldValues.data.fire_extinguisher.kg')
        ->assertFormFieldIsHidden('customFieldValues.data.fire_extinguisher.plakette_hinweis')
        ->fillForm([
            'property_id' => $property->id,
            'name' => 'Löscher 1',
            'customFieldValues' => ['data' => ['fire_extinguisher' => ['kg' => 6, 'plakette' => true, 'plakette_hinweis' => 'bis 2027', 'klasse' => 'b']]],
        ])
        ->assertFormFieldIsVisible('customFieldValues.data.fire_extinguisher.plakette_hinweis')
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->where('name', 'Löscher 1')->firstOrFail();
    $values = CustomFieldValue::query()->where('record_type', Asset::class)->where('record_id', $asset->id)->firstOrFail();

    expect($values->entity)->toBe('asset')
        ->and($values->valueOf('fire_extinguisher', 'kg'))->toEqual(6)
        ->and($values->valueOf('fire_extinguisher', 'plakette'))->toBeTrue()
        ->and($values->valueOf('fire_extinguisher', 'klasse'))->toBe('b')
        ->and($asset->customFieldValues->valueOf('fire_extinguisher', 'plakette_hinweis'))->toBe('bis 2027');
});

it('loads and updates existing values on the edit page', function () {
    loginAsAdmin();
    fireExtinguisherSection();
    $asset = assetOfType(fireExtinguisherType());
    CustomFieldValue::query()->create(['record_type' => Asset::class, 'record_id' => $asset->id, 'data' => ['fire_extinguisher' => ['kg' => 6]]]);

    Livewire::test(EditAsset::class, ['record' => $asset->getRouteKey()])
        ->assertFormSet(['customFieldValues.data.fire_extinguisher.kg' => 6])
        ->fillForm(['customFieldValues' => ['data' => ['fire_extinguisher' => ['kg' => 9]]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(CustomFieldValue::query()->where('record_id', $asset->id)->count())->toBe(1)
        ->and($asset->fresh()->customFieldValues->valueOf('fire_extinguisher', 'kg'))->toEqual(9);
});

it('shows the values in the view page and hides the section for other asset types', function () {
    loginAsAdmin();
    fireExtinguisherSection();
    $loescher = assetOfType(fireExtinguisherType());
    CustomFieldValue::query()->create(['record_type' => Asset::class, 'record_id' => $loescher->id, 'data' => ['fire_extinguisher' => ['kg' => 6, 'plakette' => true, 'plakette_hinweis' => 'bis 2027']]]);

    Livewire::test(ViewAsset::class, ['record' => $loescher->getRouteKey()])
        ->assertSee('Feuerlöscher')
        ->assertSee('Kilogramm')
        ->assertSee('6 kg')
        ->assertSee('bis 2027');

    $heizung = assetOfType(AssetType::query()->firstOrCreate(['name' => 'Heizung']), 'Heizung 1');

    Livewire::test(ViewAsset::class, ['record' => $heizung->getRouteKey()])
        ->assertDontSee('Kilogramm');
});

it('adds searchable and sortable list columns for fields flagged for the table', function () {
    loginAsAdmin();
    fireExtinguisherSection(['visible_when' => []]);
    $type = fireExtinguisherType();
    $small = assetOfType($type, 'Klein');
    $big = assetOfType($type, 'Groß');
    CustomFieldValue::query()->create(['record_type' => Asset::class, 'record_id' => $small->id, 'data' => ['fire_extinguisher' => ['kg' => 2, 'klasse' => 'A']]]);
    CustomFieldValue::query()->create(['record_type' => Asset::class, 'record_id' => $big->id, 'data' => ['fire_extinguisher' => ['kg' => 777, 'klasse' => 'b']]]);

    $columns = collect(CustomFields::tableColumns('asset'))->map(fn ($column) => $column->getName())->all();

    expect($columns)->toBe([
        'customFieldValues.data.fire_extinguisher.kg',
        'customFieldValues.data.fire_extinguisher.plakette',
        'customFieldValues.data.fire_extinguisher.klasse',
    ]);

    Livewire::test(ListAssets::class)
        ->assertCanSeeTableRecords([$small, $big])
        ->assertTableColumnExists('customFieldValues.data.fire_extinguisher.kg')
        ->assertTableColumnFormattedStateSet('customFieldValues.data.fire_extinguisher.kg', '777 kg', $big)
        ->assertTableColumnFormattedStateSet('customFieldValues.data.fire_extinguisher.klasse', 'B', $big)
        ->sortTable('customFieldValues.data.fire_extinguisher.kg', 'desc')
        ->assertCanSeeTableRecords([$big, $small], inOrder: true)
        ->searchTable('777')
        ->assertCanSeeTableRecords([$big])
        ->assertCanNotSeeTableRecords([$small]);
});

it('keeps type-specific fields out of the asset list but offers them for the plugin and export', function () {
    loginAsAdmin();
    fireExtinguisherSection();
    $asset = assetOfType(fireExtinguisherType());

    expect(collect(CustomFields::tableColumns('asset'))->map(fn ($column) => $column->getName())->all())
        ->toContain('customFieldValues.data.fire_extinguisher.kg');

    Livewire::test(ListAssets::class)
        ->assertCanSeeTableRecords([$asset])
        ->assertTableColumnDoesNotExist('customFieldValues.data.fire_extinguisher.kg')
        ->assertTableColumnDoesNotExist('customFieldValues.data.fire_extinguisher.klasse');
});

it('only renders sections of the current tenant', function () {
    loginAsAdmin();
    $other = Organization::factory()->create();
    fireExtinguisherSection(['tenants' => [$other->getKey()]]);

    expect(CustomFields::formComponents('asset'))->toBe([]);

    inOrganization($other, function (): void {
        CustomFields::flush();

        expect(CustomFields::formComponents('asset'))->toHaveCount(1);
    });
});

it('keeps resources working when a definition is broken or the entity is unknown', function () {
    loginAsAdmin();
    fireExtinguisherSection(['fields' => [['type' => 'does_not_exist', 'data' => ['key' => 'x', 'label' => 'X']], 'not-an-array']]);

    expect(CustomFields::formComponents('asset'))->toBe([])
        ->and(CustomFields::tableColumns('asset'))->toBe([])
        ->and(CustomFields::formComponents('unknown_entity'))->toBe([]);

    Livewire::test(CreateAsset::class)->assertOk();
});

it('translates section and field labels for the current locale', function () {
    loginAsAdmin();
    $section = fireExtinguisherSection();

    app()->setLocale('en');

    expect($section->translatedLabel())->toBe('Fire extinguisher')
        ->and(Translatable::text($section->fields[0]['data']))->toBe('Kilograms')
        ->and(Translatable::text($section->fields[1]['data']))->toBe('Prüfplakette vorhanden')
        ->and(collect(CustomFields::tableColumns('asset'))->first()->getLabel())->toBe('Kilograms');

    app()->setLocale('de');

    expect($section->translatedLabel())->toBe('Feuerlöscher');
});

it('resolves entity references and record attributes in conditions', function () {
    loginAsAdmin();
    $type = fireExtinguisherType();
    $asset = assetOfType($type);

    $normalized = ConditionResolver::normalize([['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => '@asset_type:Feuerlöscher']]);

    expect($normalized[0]['value'])->toBe((string) $type->id)
        ->and(ConditionResolver::passesForRecord($normalized, $asset, recordOnly: true))->toBeTrue()
        ->and(ConditionResolver::passesForRecord([['field' => 'record.name', 'operator' => 'equals', 'value' => 'anders']], $asset, recordOnly: true))->toBeFalse()
        ->and(ConditionResolver::passesForRecord([['field' => 'kg', 'operator' => 'filled']], $asset, ['kg' => 6]))->toBeTrue()
        ->and(ConditionResolver::normalize([['field' => 'x', 'operator' => 'equals', 'value' => '@asset_type:Gibt es nicht']])[0]['value'])->toBe('@asset_type:Gibt es nicht')
        ->and(ConditionResolver::normalize([['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => 'Feuerlöscher']])[0]['value'])->toBe((string) $type->id)
        ->and(ConditionResolver::normalize([['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => (string) $type->id]])[0]['value'])->toBe((string) $type->id)
        ->and(ConditionResolver::normalize([['field' => 'record.name', 'operator' => 'equals', 'value' => 'Feuerlöscher']])[0]['value'])->toBe('Feuerlöscher');
});

it('lets an admin design a section in the admin panel and locks stored keys', function () {
    loginAsAdminInAdminPanel();

    expect(CustomFieldSectionResource::canViewAny())->toBeTrue();

    $this->get(CustomFieldSectionResource::getUrl('index'))->assertOk()->assertSee('Zusatzfelder');

    Livewire::test(CreateCustomFieldSection::class)
        ->fillForm([
            'entity' => 'asset',
            'label' => 'Feuerlöscher',
            'translations' => ['en' => ['label' => 'Fire extinguisher']],
            'key' => 'fire_extinguisher',
            'tenants' => [defaultOrganization()->getKey()],
            'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => (string) fireExtinguisherType()->id]],
            'fields' => [
                ['type' => 'number', 'data' => ['key' => 'kg', 'label' => 'Kilogramm', 'unit' => 'kg', 'in_table' => true]],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $section = CustomFieldSection::query()->where('key', 'fire_extinguisher')->firstOrFail();

    expect($section->entity)->toBe('asset')
        ->and($section->translations['en']['label'])->toBe('Fire extinguisher')
        ->and($section->tenants()->pluck('custom_fields_section_tenant.tenant_id')->all())->toBe([defaultOrganization()->getKey()])
        ->and($section->fields[0]['data']['in_table'])->toBeTrue()
        ->and($section->isKeyStored('kg'))->toBeTrue()
        ->and($section->isKeyStored('neu'))->toBeFalse()
        ->and(CustomFieldSectionResource::attributeOptions('asset'))->toHaveKey('record.asset_type_id')
        ->and(CustomFieldSectionResource::referenceEntity('record.asset_type_id'))->toBe('asset_type')
        ->and(CustomFieldSectionResource::referenceEntity('record.name'))->toBeNull();

    Livewire::test(EditCustomFieldSection::class, ['record' => $section->getRouteKey()])
        ->assertFormSet(['key' => 'fire_extinguisher'])
        ->fillForm(['label' => 'Feuerlöscher (neu)'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($section->fresh()->label)->toBe('Feuerlöscher (neu)');
});

it('rejects a duplicate key for the same entity', function () {
    loginAsAdminInAdminPanel();
    fireExtinguisherSection();

    Livewire::test(CreateCustomFieldSection::class)
        ->fillForm(['entity' => 'asset', 'label' => 'Nochmal', 'key' => 'fire_extinguisher'])
        ->call('create')
        ->assertHasFormErrors(['key']);

    Livewire::test(CreateCustomFieldSection::class)
        ->fillForm(['entity' => 'property', 'label' => 'Objekt-Zusatz', 'key' => 'fire_extinguisher'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('remaps stored values after a model rename', function () {
    loginAsAdmin();
    $asset = assetOfType(fireExtinguisherType());
    CustomFieldValue::query()->create(['record_type' => 'App\\Models\\Legacy', 'record_id' => $asset->id, 'entity' => null, 'data' => []]);

    $this->artisan('custom-fields:remap', ['from' => 'App\\Models\\Legacy', 'to' => Asset::class])
        ->assertSuccessful();

    $value = CustomFieldValue::query()->where('record_id', $asset->id)->firstOrFail();

    expect($value->record_type)->toBe(Asset::class)
        ->and($value->entity)->toBe('asset');
});

it('offers sibling fields, record attributes and matching values in the condition editor', function () {
    $blocks = [
        'a' => ['type' => 'select', 'data' => ['key' => 'klasse', 'label' => 'Brandklasse', 'options' => [['label' => 'A'], ['label' => 'B', 'value' => 'b']]]],
        'b' => ['type' => 'toggle', 'data' => ['key' => 'plakette', 'label' => 'Plakette']],
        'c' => ['type' => 'text', 'data' => ['key' => 'hinweis', 'label' => 'Hinweis']],
    ];
    $get = new class($blocks) extends Get
    {
        public function __construct(private array $blocks) {}

        public function __invoke(string|Component $path = '', bool $isAbsolute = false): mixed
        {
            return $path === '../../../../' ? $this->blocks : null;
        }
    };

    expect(DesignerSchema::siblingFieldOptions($get))->toBe(['klasse' => 'Brandklasse (klasse)', 'plakette' => 'Plakette (plakette)', 'hinweis' => 'Hinweis (hinweis)'])
        ->and(DesignerSchema::siblingValueOptions('klasse', $get))->toBe(['A' => 'A', 'b' => 'B'])
        ->and(DesignerSchema::siblingValueOptions('plakette', $get))->toBe(['1' => 'Ja', '0' => 'Nein'])
        ->and(DesignerSchema::siblingValueOptions('hinweis', $get))->toBe([])
        ->and(DesignerSchema::siblingValueOptions('record.asset_type_id', $get))->toBe([]);
});
