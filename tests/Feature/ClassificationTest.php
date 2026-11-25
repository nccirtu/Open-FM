<?php

use App\Filament\Admin\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Filament\CostCenterField;
use App\Filament\Fm\Pages\AssetCategoriesPage;
use App\Filament\Fm\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Pm\Pages\CostCentersPage;
use App\Filament\RelationManagers\CostEntriesRelationManager;
use App\Filament\Resources\AssetResource;
use App\Filament\Resources\AssetResource\Pages\CreateAsset;
use App\Filament\Resources\AssetResource\Pages\EditAsset;
use App\Filament\Resources\AssetResource\Pages\ViewAsset;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\BudgetPositionsRelationManager;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\AssetTypeClassification;
use App\Models\ClassificationNode;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\Ticket;
use App\Services\Classification\ClassificationTemplateImporter;
use Cafm\CustomFields\CustomFields;
use Cafm\CustomFields\Models\CustomFieldSection;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

const DIN276_NODE_COUNT = 21;

function din276Template(): array
{
    return [
        'nodes' => [
            ['code' => '100', 'name' => 'Grundstück'],
            ['code' => '200', 'name' => 'Vorbereitende Maßnahmen'],
            ['code' => '300', 'name' => 'Bauwerk — Baukonstruktionen'],
            ['code' => '400', 'name' => 'Bauwerk — Technische Anlagen', 'children' => [
                ['code' => '410', 'name' => 'Abwasser-, Wasser-, Gasanlagen', 'children' => [
                    ['code' => '412', 'name' => 'Wasseranlagen'],
                ]],
                ['code' => '420', 'name' => 'Wärmeversorgungsanlagen', 'children' => [
                    ['code' => '421', 'name' => 'Wärmeerzeugungsanlagen', 'asset_types' => ['Heizung']],
                ]],
                ['code' => '430', 'name' => 'Raumlufttechnische Anlagen', 'children' => [
                    ['code' => '434', 'name' => 'Kälteanlagen', 'asset_types' => ['Klimaanlage', 'Kühlschrank']],
                ]],
                ['code' => '440', 'name' => 'Elektrische Anlagen'],
                ['code' => '450', 'name' => 'Kommunikationsanlagen', 'children' => [
                    ['code' => '457', 'name' => 'Gefahrenmelde- und Alarmanlagen', 'asset_types' => ['Rauchmelder']],
                ]],
                ['code' => '460', 'name' => 'Förderanlagen', 'children' => [
                    ['code' => '461', 'name' => 'Aufzugsanlagen', 'asset_types' => ['Aufzug']],
                ]],
                ['code' => '470', 'name' => 'Nutzungsspezifische Anlagen', 'children' => [
                    ['code' => '475', 'name' => 'Feuerlöschanlagen', 'asset_types' => ['Feuerlöscher']],
                ]],
            ]],
            ['code' => '500', 'name' => 'Außenanlagen'],
            ['code' => '600', 'name' => 'Ausstattung und Kunstwerke', 'children' => [
                ['code' => '610', 'name' => 'Allgemeine Ausstattung'],
            ]],
            ['code' => '700', 'name' => 'Baunebenkosten'],
        ],
        'custom_fields' => [
            [
                'entity' => 'asset',
                'key' => 'aufzugsanlage',
                'label' => 'Aufzugsanlage',
                'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'equals', 'value' => '@asset_type:Aufzug']],
                'fields' => [
                    ['type' => 'text', 'data' => ['key' => 'amtliche_kennnummer', 'label' => 'Amtliche Kennnummer']],
                ],
            ],
            [
                'entity' => 'asset',
                'key' => 'kaeltetechnik',
                'label' => 'Kältetechnik',
                'visible_when' => [['field' => 'record.asset_type_id', 'operator' => 'in', 'value' => '@asset_type:Klimaanlage|@asset_type:Kühlschrank']],
                'fields' => [
                    ['type' => 'text', 'data' => ['key' => 'kaeltemitteltyp', 'label' => 'Kältemitteltyp']],
                ],
            ],
        ],
    ];
}

function useClassificationTemplateDirectory(bool $withTemplate = true): string
{
    $directory = sys_get_temp_dir().'/classification-templates-'.uniqid();

    if ($withTemplate) {
        File::ensureDirectoryExists($directory);
        File::put($directory.'/din-276.json', json_encode(din276Template(), JSON_UNESCAPED_UNICODE));
    }

    app()->instance(ClassificationTemplateImporter::class, new ClassificationTemplateImporter($directory));

    return $directory;
}

beforeEach(function (): void {
    useClassificationTemplateDirectory();
});

it('imports the classification template idempotently and scoped to the importing tenant', function () {
    loginAsAdmin();
    $template = din276Template();

    $importer = app(ClassificationTemplateImporter::class);
    $count = $importer->import($template['nodes'], defaultOrganization());

    expect($count)->toBe(DIN276_NODE_COUNT)
        ->and(ClassificationNode::query()->where('code', '475')->exists())->toBeTrue()
        ->and(ClassificationNode::query()->where('code', '610')->exists())->toBeTrue();

    $secondImportCount = $importer->import($template['nodes'], defaultOrganization());
    expect($secondImportCount)->toBe(0)
        ->and(ClassificationNode::query()->count())->toBe($count);

    $other = Organization::factory()->create();
    expect(ClassificationNode::query()->where('organization_id', $other->getKey())->count())->toBe(0);
});

it('imports one of the fixed templates via the artisan command idempotently', function () {
    loginAsAdmin();

    $this->artisan('classifications:import', [
        'file' => 'din-276.json',
        '--organization' => defaultOrganization()->getKey(),
    ])->assertSuccessful();

    $root = ClassificationNode::query()->where('code', '400')->firstOrFail();
    $child = ClassificationNode::query()->where('code', '430')->firstOrFail();

    expect($child->parent_id)->toBe($root->getKey());

    $count = ClassificationNode::query()->count();

    $this->artisan('classifications:import', [
        'file' => 'din-276.json',
        '--organization' => defaultOrganization()->getKey(),
    ])->assertSuccessful();

    expect(ClassificationNode::query()->count())->toBe($count);

    $this->artisan('classifications:import', ['file' => 'gibt-es-nicht.json'])->assertFailed();
});

it('imports the template for a new tenant right when it is created, like the maintenance catalog', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(CreateOrganization::class)
        ->fillForm(['name' => 'Neuer Mandant', 'slug' => 'neuer-mandant', 'default_locale' => 'de', 'classification_template' => 'din276'])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->where('slug', 'neuer-mandant')->firstOrFail();

    expect(ClassificationNode::query()->where('organization_id', $organization->getKey())->count())->toBe(DIN276_NODE_COUNT);
});

it('offers no template and imports nothing when the template directory is missing', function () {
    loginAsAdmin();
    useClassificationTemplateDirectory(withTemplate: false);

    expect(ClassificationTemplateImporter::templateOptions())->toBe([])
        ->and(app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276'))->toBe(0)
        ->and(ClassificationNode::query()->count())->toBe(0);

    $this->artisan('classifications:import', [
        'file' => 'din-276.json',
        '--organization' => defaultOrganization()->getKey(),
    ])->assertFailed();
});

it('assigns a global asset type to a node in the classification tree via the asset categories page', function () {
    loginAsAdminInFmPanel();
    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    $customType = AssetType::query()->create(['name' => 'Sonderanlage Kühlregal']);
    $node = ClassificationNode::query()->where('code', '475')->firstOrFail();

    Livewire::test(AssetCategoriesPage::class)
        ->searchTable('Sonderanlage')
        ->assertCanSeeTableRecords([$customType])
        ->call('updateTableColumnState', 'classification_node_id', (string) $customType->getKey(), (string) $node->getKey())
        ->assertSuccessful();

    expect($customType->categoryForCurrentTenant()?->id)->toBe($node->getKey());

    $other = Organization::factory()->create();
    inOrganization($other, function () use ($customType): void {
        expect($customType->categoryForCurrentTenant())->toBeNull();
    });
});

it('classifies the template\'s own asset types on import, creating missing ones without overwriting a later manual change', function () {
    loginAsAdminInFmPanel();

    expect(AssetType::query()->where('name', 'Rauchmelder')->exists())->toBeFalse();

    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    $smokeDetector = AssetType::query()->firstOrCreate(['name' => 'Rauchmelder']);
    $alarmNode = ClassificationNode::query()->where('code', '457')->firstOrFail();
    expect($smokeDetector->categoryForCurrentTenant()?->getKey())->toBe($alarmNode->getKey());

    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
    $reassignedNode = ClassificationNode::query()->where('code', '412')->firstOrFail();

    Livewire::test(AssetCategoriesPage::class)
        ->call('updateTableColumnState', 'classification_node_id', (string) $fireExtinguisher->getKey(), (string) $reassignedNode->getKey())
        ->assertSuccessful();

    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    expect($fireExtinguisher->categoryForCurrentTenant()?->getKey())->toBe($reassignedNode->getKey());
});

it('lets an admin import a template and manage nodes on the cost centers page', function () {
    loginAsAdmin();

    Livewire::test(CostCentersPage::class)
        ->assertCanSeeTableRecords([])
        ->callAction('import', ['template' => 'din276'])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $node = ClassificationNode::query()->where('code', '440')->firstOrFail();

    Livewire::test(CostCentersPage::class)
        ->searchTable('440')
        ->assertCanSeeTableRecords([$node])
        ->callAction(TestAction::make('edit')->table($node), ['code' => '440', 'name' => 'Elektro (angepasst)'])
        ->assertHasNoActionErrors();

    expect($node->fresh()->name)->toBe('Elektro (angepasst)');

    Livewire::test(CostCentersPage::class)
        ->callAction('create', ['code' => '999', 'name' => 'Sonderkonto'])
        ->assertHasNoActionErrors();

    $created = ClassificationNode::query()->where('code', '999')->firstOrFail();

    Livewire::test(CostCentersPage::class)
        ->callAction(TestAction::make('delete')->table($created))
        ->assertHasNoActionErrors();

    expect(ClassificationNode::query()->whereKey($created->getKey())->exists())->toBeFalse();
});

it('offers a select once the tenant has a classification tree, otherwise a free text fallback', function () {
    loginAsAdmin();

    expect(CostCenterField::make())->toBeInstanceOf(TextInput::class);

    ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro']);

    expect(CostCenterField::make())->toBeInstanceOf(Select::class);
});

it('stores the cost center on a budget position and prefers it over the free text label', function () {
    loginAsAdmin();
    $project = Project::factory()->create(['budget' => 100000]);
    $costCenter = ClassificationNode::query()->create(['code' => '440', 'name' => 'Elektro']);

    Livewire::test(BudgetPositionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Elektroinstallation', 'cost_center_id' => $costCenter->getKey(), 'budget' => 10000, 'sort' => 1])
        ->assertHasNoActionErrors();

    $position = $project->budgetPositions()->where('name', 'Elektroinstallation')->firstOrFail();

    expect($position->cost_center_id)->toBe($costCenter->getKey())
        ->and($position->costGroupLabel())->toBe('440 — Elektro');
});

it('keeps the free text cost group working for tenants without a classification tree', function () {
    loginAsAdmin();
    $project = Project::factory()->create(['budget' => 50000]);

    Livewire::test(BudgetPositionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Bodenbelag', 'cost_group' => '350', 'budget' => 5000, 'sort' => 1])
        ->assertHasNoActionErrors();

    $position = $project->budgetPositions()->where('name', 'Bodenbelag')->firstOrFail();

    expect($position->cost_center_id)->toBeNull()
        ->and($position->costGroupLabel())->toBe('350');
});

it('lets a cost entry carry a cost center independently of what it is booked against', function (string $model, string $pageClass, string $login) {
    requiresPlugin('receipt-capture');

    $login();
    $owner = $model::factory()->create();
    $costCenter = ClassificationNode::query()->create(['code' => '475', 'name' => 'Brandschutzanlagen']);

    Livewire::test(CostEntriesRelationManager::class, ['ownerRecord' => $owner, 'pageClass' => $pageClass])
        ->callAction(TestAction::make('captureReceipt')->table(), [
            'receipt_type' => 'expense',
            'booked_on' => '2026-09-06',
            'gross_amount' => 100,
            'cost_center_id' => $costCenter->getKey(),
        ])
        ->assertHasNoActionErrors();

    $entry = $owner->costEntries()->sole();

    expect($entry->cost_center_id)->toBe($costCenter->getKey())
        ->and($entry->costCenter->label())->toBe('475 — Brandschutzanlagen');
})->with([
    'ticket' => [Ticket::class, ViewTicket::class, 'loginAsAdminInFmPanel'],
    'asset' => [Asset::class, ViewAsset::class, 'loginAsAdmin'],
]);

it('lets the same classification node serve as both an asset category and a cost center', function () {
    loginAsAdminInFmPanel();
    $node = ClassificationNode::query()->create(['code' => '475', 'name' => 'Brandschutzanlagen']);
    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);

    Livewire::test(AssetCategoriesPage::class)
        ->call('updateTableColumnState', 'classification_node_id', (string) $fireExtinguisher->getKey(), (string) $node->getKey())
        ->assertSuccessful();

    $project = Project::factory()->create(['budget' => 20000]);

    Livewire::test(BudgetPositionsRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
        ->callAction(TestAction::make('create')->table(), ['name' => 'Brandschutz-Wartung', 'cost_center_id' => $node->getKey(), 'budget' => 2000, 'sort' => 1])
        ->assertHasNoActionErrors();

    expect($fireExtinguisher->categoryForCurrentTenant()?->id)->toBe($node->getKey())
        ->and($project->budgetPositions()->where('name', 'Brandschutz-Wartung')->firstOrFail()->cost_center_id)->toBe($node->getKey());
});

it('derives a per-tenant display code for an asset type from its classification node', function () {
    loginAsAdminInFmPanel();
    $node = ClassificationNode::query()->create(['code' => '475', 'name' => 'Brandschutzanlagen']);
    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
    $smokeDetector = AssetType::query()->firstOrCreate(['name' => 'Rauchmelder']);

    AssetTypeClassification::query()->create(['asset_type_id' => $fireExtinguisher->getKey(), 'classification_node_id' => $node->getKey()]);
    AssetTypeClassification::query()->create(['asset_type_id' => $smokeDetector->getKey(), 'classification_node_id' => $node->getKey()]);

    expect($fireExtinguisher->displayCodeForCurrentTenant())->toBe('475-1')
        ->and($fireExtinguisher->displayLabelForCurrentTenant())->toBe('475-1 Feuerlöscher')
        ->and($smokeDetector->displayCodeForCurrentTenant())->toBe('475-2')
        ->and(AssetType::query()->firstOrCreate(['name' => 'Heizung'])->displayLabelForCurrentTenant())->toBe('Heizung');
});

it('narrows the asset type options to the chosen classification group, including deeper descendants', function () {
    loginAsAdminInFmPanel();
    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    $root = ClassificationNode::query()->where('code', '400')->firstOrFail();
    $group = ClassificationNode::query()->where('code', '470')->firstOrFail();
    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
    $heating = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    expect(AssetResource::classificationOptions(null))->toHaveKey($root->getKey())
        ->and(AssetResource::classificationOptions($root->getKey()))->toHaveKey($group->getKey())
        ->and(AssetResource::assetTypeOptions($group->getKey()))
        ->toHaveKey($fireExtinguisher->getKey())
        ->not->toHaveKey($heating->getKey())
        ->and(AssetResource::assetTypeOptions(null))->toHaveKey($heating->getKey());
});

it('creates an asset via the cascading classification selects and pre-fills them back on edit', function () {
    loginAsAdminInFmPanel();
    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    $root = ClassificationNode::query()->where('code', '400')->firstOrFail();
    $group = ClassificationNode::query()->where('code', '470')->firstOrFail();
    $fireExtinguisher = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);

    $property = Property::factory()->create();

    Livewire::test(CreateAsset::class)
        ->fillForm([
            'property_id' => $property->getKey(),
            'name' => 'Pulverlöscher Lager',
            'classification_root_id' => $root->getKey(),
            'classification_group_id' => $group->getKey(),
            'asset_type_id' => $fireExtinguisher->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::query()->where('name', 'Pulverlöscher Lager')->firstOrFail();

    Livewire::test(EditAsset::class, ['record' => $asset->getRouteKey()])
        ->assertFormSet([
            'classification_root_id' => $root->getKey(),
            'classification_group_id' => $group->getKey(),
        ]);
});

it('imports the template\'s custom field sections globally, idempotently across tenants', function () {
    requiresPlugin('custom-fields');

    loginAsAdminInFmPanel();

    expect(CustomFieldSection::query()->where('entity', 'asset')->where('key', 'aufzugsanlage')->exists())->toBeFalse();

    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');

    $section = CustomFieldSection::query()->where('entity', 'asset')->where('key', 'aufzugsanlage')->firstOrFail();

    expect($section->label)->toBe('Aufzugsanlage')
        ->and(collect($section->fieldSpecs())->pluck('data.key')->all())->toContain('amtliche_kennnummer');

    $countAfterFirstImport = CustomFieldSection::query()->count();

    $other = Organization::factory()->create();
    app(ClassificationTemplateImporter::class)->importTemplate($other, 'din276');

    expect(CustomFieldSection::query()->count())->toBe($countAfterFirstImport);
});

it('shows a custom field section for every asset type it lists via the shared "in" condition', function () {
    requiresPlugin('custom-fields');

    loginAsAdminInFmPanel();
    app(ClassificationTemplateImporter::class)->importTemplate(defaultOrganization(), 'din276');
    CustomFields::flush();

    $property = Property::factory()->create();
    $klimaanlage = AssetType::query()->firstOrCreate(['name' => 'Klimaanlage']);
    $kuehlschrank = AssetType::query()->firstOrCreate(['name' => 'Kühlschrank']);
    $heating = AssetType::query()->firstOrCreate(['name' => 'Heizung']);

    $klimaAsset = Asset::factory()->create(['property_id' => $property->getKey(), 'asset_type_id' => $klimaanlage->getKey()]);
    $coolerAsset = Asset::factory()->create(['property_id' => $property->getKey(), 'asset_type_id' => $kuehlschrank->getKey()]);
    $heatingAsset = Asset::factory()->create(['property_id' => $property->getKey(), 'asset_type_id' => $heating->getKey()]);

    Livewire::test(ViewAsset::class, ['record' => $klimaAsset->getRouteKey()])->assertSee('Kältemitteltyp');
    Livewire::test(ViewAsset::class, ['record' => $coolerAsset->getRouteKey()])->assertSee('Kältemitteltyp');
    Livewire::test(ViewAsset::class, ['record' => $heatingAsset->getRouteKey()])->assertDontSee('Kältemitteltyp');
});
