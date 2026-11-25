<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Cafm\FormBuilder\Enums\FormStatus;
use Cafm\FormBuilder\Events\FormSubmitted;
use Cafm\FormBuilder\Filament\Pages\FillFormPage;
use Cafm\FormBuilder\Filament\Pages\FormSubmissionsPage;
use Cafm\FormBuilder\Filament\Resources\FormResource;
use Cafm\FormBuilder\Filament\Resources\FormResource\Pages\CreateForm;
use Cafm\FormBuilder\Filament\Resources\FormResource\Pages\EditForm;
use Cafm\FormBuilder\Filament\Resources\FormResource\Pages\ListForms;
use Cafm\FormBuilder\Jobs\SendSubmissionWebhook;
use Cafm\FormBuilder\Models\Form;
use Cafm\FormBuilder\Models\FormSubmission;
use Cafm\FormBuilder\Notifications\SubmissionReceived;
use Cafm\FormBuilder\Schema\ConditionEvaluator;
use Cafm\FormBuilder\Support\EntityRegistry;
use Cafm\FormBuilder\Support\FormNavigation;
use Cafm\FormBuilder\Support\FormPermissions;
use Cafm\FormBuilder\Support\FormTemplate;
use Cafm\FormBuilder\Support\SubmissionCsvExporter;
use Cafm\FormBuilder\Support\SubmissionPresenter;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * @return array<int, array<string, mixed>>
 */
function inspectionLikeSchema(): array
{
    return [
        [
            'key' => 'aussenanlagen',
            'label' => 'Außenanlagen',
            'columns' => 2,
            'fields' => [
                ['type' => 'radio', 'data' => ['key' => 'klingel', 'label' => 'Klingel', 'options' => [['label' => 'Ja'], ['label' => 'Nein']], 'required' => true]],
                ['type' => 'text', 'data' => ['key' => 'klingel_position', 'label' => 'Position', 'visible_when' => [['field' => 'klingel', 'operator' => 'equals', 'value' => 'Ja']]]],
                ['type' => 'radio_other', 'data' => ['key' => 'bodenbelag', 'label' => 'Bodenbelag', 'options' => [['label' => 'Pflaster'], ['label' => 'Asphalt']]]],
                ['type' => 'group', 'data' => ['key' => 'werbepylon', 'label' => 'Werbepylon', 'presence_toggle' => true, 'fields' => [
                    ['type' => 'number', 'data' => ['key' => 'anzahl', 'label' => 'Anzahl', 'unit' => 'Stk.']],
                ]]],
                ['type' => 'repeater', 'data' => ['key' => 'muelltonnen', 'label' => 'Mülltonnen', 'fields' => [
                    ['type' => 'select', 'data' => ['key' => 'art', 'label' => 'Art', 'options' => [['label' => 'Papier'], ['label' => 'Restmüll']]]],
                    ['type' => 'number', 'data' => ['key' => 'anzahl', 'label' => 'Anzahl']],
                ]]],
            ],
        ],
    ];
}

function publishedForm(array $overrides = []): Form
{
    $tenantIds = $overrides['tenants'] ?? [defaultOrganization()->getKey()];
    unset($overrides['tenants']);

    $form = Form::query()->create(array_merge([
        'name' => 'Inspektion',
        'slug' => 'inspektion',
        'panels' => ['fm'],
        'subject_entity' => 'property',
        'role_permissions' => [['role' => 'FM', 'abilities' => ['fill', 'view_own']]],
        'settings' => ['pdf_enabled' => true],
        'schema' => inspectionLikeSchema(),
    ], $overrides));

    $form->tenants()->sync($tenantIds);
    $form->publish();

    return $form->fresh(['publishedVersion', 'tenants']);
}

function fmUser(): User
{
    $user = User::factory()->create(['email' => 'fm@example.com']);
    $user->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
    setPermissionsTeamId(defaultOrganization()->getKey());
    $user->assignRole('FM');

    return $user;
}

beforeEach(fn () => requiresPlugin('form-builder'));

it('lets an admin design and publish a form in the admin panel', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(CreateForm::class)
        ->fillForm([
            'name' => 'Inspektion',
            'slug' => 'inspektion',
            'tenants' => [defaultOrganization()->getKey()],
            'panels' => ['fm', 'pm'],
            'subject_entity' => 'property',
            'schema' => inspectionLikeSchema(),
            'role_permissions' => [['role' => 'FM', 'abilities' => ['fill', 'view_all']]],
            'settings' => ['pdf_enabled' => true, 'webhook_url' => 'https://example.test/hook'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $form = Form::query()->withoutGlobalScopes()->where('slug', 'inspektion')->firstOrFail();

    expect($form->status)->toBe(FormStatus::Draft)
        ->and($form->tenants()->pluck('form_builder_form_tenant.tenant_id')->all())->toBe([defaultOrganization()->getKey()])
        ->and($form->panels)->toBe(['fm', 'pm'])
        ->and($form->schema[0]['fields'][0]['data']['key'])->toBe('klingel')
        ->and($form->setting('webhook_url'))->toBe('https://example.test/hook');

    $version = $form->publish();

    expect($form->fresh()->status)->toBe(FormStatus::Published)
        ->and($version->version)->toBe(1)
        ->and($form->fresh()->activeSchema()[0]['fields'][3]['data']['fields'][0]['data']['unit'])->toBe('Stk.');
});

it('authorizes the designer for admins in the global admin panel without tenant context', function () {
    loginAsAdminInAdminPanel();
    setPermissionsTeamId(null);

    expect(FormResource::canViewAny())->toBeTrue()
        ->and(FormResource::canCreate())->toBeTrue();

    $this->get(FormResource::getUrl('index'))->assertOk()->assertSee('Formular-Designer');

    $this->actingAs(fmUser());
    setPermissionsTeamId(null);

    expect(FormResource::canViewAny())->toBeFalse();
});

it('edits the fields of one section in the designer slide-over', function () {
    loginAsAdminInAdminPanel();
    $form = Form::query()->create(['name' => 'Check', 'slug' => 'check', 'panels' => ['fm'], 'schema' => inspectionLikeSchema()]);

    $component = Livewire::test(EditForm::class, ['record' => $form->getKey()]);
    $itemKey = array_key_first($component->instance()->data['schema']);

    $component
        ->callAction(TestAction::make('editFields')->schemaComponent('schema')->arguments(['item' => $itemKey]), [
            'fields' => [
                ['type' => 'text', 'data' => ['key' => 'neu', 'label' => 'Neues Feld', 'required' => true]],
            ],
        ])
        ->assertHasNoActionErrors()
        ->call('save')
        ->assertHasNoFormErrors();

    expect($form->fresh()->schema[0]['fields'])->toHaveCount(1)
        ->and($form->fresh()->schema[0]['fields'][0]['data']['key'])->toBe('neu')
        ->and($form->fresh()->schema[0]['key'])->toBe('aussenanlagen');
});

it('builds navigation items only for published forms of the panel and the current tenant', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm();
    $other = publishedForm(['slug' => 'pm-only', 'name' => 'PM only', 'panels' => ['pm']]);
    $foreign = publishedForm(['slug' => 'foreign', 'name' => 'Foreign', 'tenants' => [Organization::factory()->create()->getKey()]]);
    $global = publishedForm(['slug' => 'global', 'name' => 'Global', 'tenants' => [], 'settings' => ['submissions_table' => false]]);

    $items = FormNavigation::itemsFor(Filament::getPanel('fm'));

    expect(collect($items)->map(fn ($item) => $item->getLabel())->all())->toBe(['Foreign', 'Foreign: Eingaben', 'Global', 'Inspektion', 'Inspektion: Eingaben'])
        ->and(FormNavigation::isVisible($form))->toBeTrue()
        ->and(FormNavigation::isVisible($global))->toBeTrue()
        ->and(FormNavigation::isVisible($foreign))->toBeFalse()
        ->and(collect(FormNavigation::itemsFor(Filament::getPanel('pm')))->map(fn ($item) => $item->getLabel())->all())->toBe(['PM only', 'PM only: Eingaben']);
});

it('renders the published form, stores a submission and triggers event and webhook', function () {
    Event::fake([FormSubmitted::class]);
    Queue::fake();
    loginAsAdminInFmPanel();
    $property = Property::factory()->create();
    $form = publishedForm(['settings' => ['webhook_url' => 'https://example.test/hook']]);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])
        ->assertOk()
        ->assertSee('Außenanlagen')
        ->fillForm([
            'subject_id' => $property->getKey(),
            'data' => [
                'aussenanlagen' => [
                    'klingel' => 'Nein',
                    'klingel_position' => 'sollte verschwinden',
                    'bodenbelag' => '__other__',
                    'bodenbelag_other' => 'Kies',
                    'werbepylon' => ['present' => 'yes', 'anzahl' => 2],
                    'muelltonnen' => [['art' => 'Papier', 'anzahl' => 3]],
                ],
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors();

    $submission = FormSubmission::query()->firstOrFail();

    expect($submission->form_version_id)->toBe($form->published_version_id)
        ->and($submission->subject)->toBeInstanceOf(Property::class)
        ->and($submission->organization_id)->toBe(defaultOrganization()->getKey())
        ->and($submission->data['aussenanlagen']['klingel'])->toBe('Nein')
        ->and($submission->data['aussenanlagen'])->not->toHaveKey('klingel_position')
        ->and($submission->data['aussenanlagen']['werbepylon']['anzahl'])->toBe(2)
        ->and($submission->data['aussenanlagen']['muelltonnen'][0]['art'])->toBe('Papier');

    Event::assertDispatched(FormSubmitted::class, fn (FormSubmitted $event) => $event->submission->is($submission));
    Queue::assertPushed(SendSubmissionWebhook::class, fn (SendSubmissionWebhook $job) => $job->url === 'https://example.test/hook');

    $payload = SubmissionPresenter::make($submission)->toPayload();

    expect($payload['subject']['title'])->toBe($property->property_number)
        ->and($payload['sections'][0]['label'])->toBe('Außenanlagen')
        ->and(collect($payload['sections'][0]['fields'])->firstWhere('key', 'bodenbelag')['display'])->toBe('Sonstiges: Kies')
        ->and(collect($payload['sections'][0]['fields'])->firstWhere('key', 'werbepylon')['display'])->toBe('Ja')
        ->and(collect($payload['sections'][0]['fields'])->firstWhere('key', 'muelltonnen')['rows'][0][1]['display'])->toBe('3');
});

it('requires a required field and validates the subject', function () {
    loginAsAdminInFmPanel();
    publishedForm();

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])
        ->fillForm(['data' => ['aussenanlagen' => ['klingel' => null]]])
        ->call('submit')
        ->assertHasFormErrors(['subject_id' => 'required', 'data.aussenanlagen.klingel' => 'required']);
});

it('enforces role permissions per form', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm(['role_permissions' => [['role' => 'Verträge', 'abilities' => ['fill']]]]);
    $user = fmUser();

    expect(FormPermissions::canFill($form, $user))->toBeFalse()
        ->and(FormPermissions::canSee($form, $user))->toBeFalse();

    $this->actingAs($user);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])->assertForbidden();

    $form->update(['role_permissions' => [['role' => 'FM', 'abilities' => ['fill', 'view_own']]]]);
    $form = $form->fresh();

    expect(FormPermissions::canFill($form, $user))->toBeTrue()
        ->and(FormPermissions::canViewSubmissions($form, $user))->toBeTrue()
        ->and(FormPermissions::canViewSubmission($form, $user, new FormSubmission(['submitted_by' => 999])))->toBeFalse();

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])->assertOk();
});

it('lists submissions, restricts view_own users to their own entries and downloads a PDF', function () {
    $admin = loginAsAdminInFmPanel();
    $form = publishedForm();
    $user = fmUser();

    $own = FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'submitted_by' => $user->getKey(), 'data' => ['aussenanlagen' => ['klingel' => 'Ja', 'klingel_position' => 'links']], 'submitted_at' => now()]);
    $foreign = FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'submitted_by' => $admin->getKey(), 'data' => ['aussenanlagen' => ['klingel' => 'Nein']], 'submitted_at' => now()]);

    Livewire::test(FormSubmissionsPage::class, ['form' => 'inspektion'])
        ->assertOk()
        ->assertCanSeeTableRecords([$own, $foreign])
        ->callTableAction('pdf', $own)
        ->assertFileDownloaded('inspektion-'.$own->getKey().'.pdf');

    $this->actingAs($user);

    Livewire::test(FormSubmissionsPage::class, ['form' => 'inspektion'])
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('hides forms of other tenants and unpublished forms', function () {
    loginAsAdminInFmPanel();
    publishedForm(['slug' => 'foreign', 'tenants' => [Organization::factory()->create()->getKey()]]);
    Form::query()->create(['name' => 'Entwurf', 'slug' => 'entwurf', 'panels' => ['fm'], 'schema' => []]);

    Livewire::test(FillFormPage::class, ['form' => 'foreign'])->assertNotFound();
    Livewire::test(FillFormPage::class, ['form' => 'entwurf'])->assertNotFound();
});

it('offers database-sourced select options scoped to the current tenant', function () {
    loginAsAdminInFmPanel();
    $own = Asset::factory()->create(['name' => 'Eigener Lift']);
    $foreignOrganization = Organization::factory()->create();
    $foreign = inOrganization($foreignOrganization, fn () => Asset::factory()->create(['name' => 'Fremder Lift', 'organization_id' => $foreignOrganization->getKey()]));

    $options = EntityRegistry::searchOptions('asset', 'Lift');

    expect($options)->toHaveKey($own->getKey())
        ->and($options)->not->toHaveKey($foreign->getKey());

    publishedForm(['schema' => [[
        'key' => 'technik',
        'label' => 'Technik',
        'fields' => [
            ['type' => 'select', 'data' => ['key' => 'anlage', 'label' => 'Anlage', 'source' => 'entity', 'entity' => 'asset']],
            ['type' => 'radio', 'data' => ['key' => 'haupt', 'label' => 'Hauptanlage', 'source' => 'entity', 'entity' => 'asset']],
            ['type' => 'radio_other', 'data' => ['key' => 'ersatz', 'label' => 'Ersatz', 'source' => 'entity', 'entity' => 'asset']],
            ['type' => 'checkbox_list', 'data' => ['key' => 'geprueft', 'label' => 'Geprüft', 'source' => 'entity', 'entity' => 'asset']],
        ],
    ]]]);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])
        ->assertSee('Eigener Lift')
        ->assertDontSee('Fremder Lift')
        ->fillForm(['subject_id' => Property::factory()->create()->getKey(), 'data' => ['technik' => [
            'anlage' => $own->getKey(),
            'haupt' => $own->getKey(),
            'ersatz' => '__other__',
            'ersatz_other' => 'Leihgerät',
            'geprueft' => [$own->getKey()],
        ]]])
        ->call('submit')
        ->assertHasNoFormErrors();

    $submission = FormSubmission::query()->firstOrFail();
    $fields = collect(SubmissionPresenter::make($submission)->sections()[0]['fields']);

    expect($fields->firstWhere('key', 'anlage')['display'])->toBe('Eigener Lift')
        ->and($fields->firstWhere('key', 'haupt')['display'])->toBe('Eigener Lift')
        ->and($fields->firstWhere('key', 'ersatz')['display'])->toBe('Sonstiges: Leihgerät')
        ->and($fields->firstWhere('key', 'geprueft')['display'])->toBe('Eigener Lift');
});

it('edits a submission via the fill page and shows configured table columns', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm(['settings' => ['submissions_table' => true, 'table_columns' => ['aussenanlagen.klingel', 'aussenanlagen.bodenbelag']]]);
    $property = Property::factory()->create();
    $submission = FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'submitted_by' => auth()->id(), 'subject_type' => Property::class, 'subject_id' => $property->getKey(), 'data' => ['aussenanlagen' => ['klingel' => 'Ja', 'bodenbelag' => 'Pflaster']], 'submitted_at' => now()]);

    expect(collect(FormSubmissionsPage::dataColumns($form))->map(fn ($column) => $column->getName())->all())->toBe(['data.aussenanlagen.klingel', 'data.aussenanlagen.bodenbelag']);

    Livewire::withQueryParams(['submission' => $submission->getKey()])
        ->test(FillFormPage::class, ['form' => 'inspektion'])
        ->assertOk()
        ->assertSchemaStateSet(['data.aussenanlagen.klingel' => 'Ja'])
        ->fillForm(['data' => ['aussenanlagen' => ['klingel' => 'Nein']]])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect(FormSubmission::query()->count())->toBe(1)
        ->and($submission->fresh()->data['aussenanlagen']['klingel'])->toBe('Nein');

    $user = fmUser();
    $form->update(['role_permissions' => [['role' => 'FM', 'abilities' => ['fill', 'view_all']]]]);
    $this->actingAs($user);

    Livewire::withQueryParams(['submission' => $submission->getKey()])
        ->test(FillFormPage::class, ['form' => 'inspektion'])
        ->assertForbidden();
});

it('evaluates visibility conditions', function () {
    $get = fn (string $field) => ['a' => 'x', 'list' => ['1', '2'], 'empty' => null][$field] ?? null;

    expect(ConditionEvaluator::passes([['field' => 'a', 'operator' => 'equals', 'value' => 'x']], $get))->toBeTrue()
        ->and(ConditionEvaluator::passes([['field' => 'a', 'operator' => 'not_equals', 'value' => 'x']], $get))->toBeFalse()
        ->and(ConditionEvaluator::passes([['field' => 'list', 'operator' => 'in', 'value' => '2|3']], $get))->toBeTrue()
        ->and(ConditionEvaluator::passes([['field' => 'empty', 'operator' => 'blank']], $get))->toBeTrue()
        ->and(ConditionEvaluator::passes([['field' => 'a', 'operator' => 'filled'], ['field' => 'a', 'operator' => 'equals', 'value' => 'y']], $get))->toBeFalse();
});

it('creates, updates and deletes app records from repeater rows', function () {
    loginAsAdminInFmPanel();
    $property = Property::factory()->create();
    $assetType = AssetType::query()->firstOrCreate(['name' => 'Feuerlöscher']);
    $form = publishedForm(['schema' => [[
        'key' => 'brandschutz',
        'label' => 'Brandschutz',
        'fields' => [
            ['type' => 'repeater', 'data' => [
                'key' => 'loescher', 'label' => 'Feuerlöscher',
                'fields' => [
                    ['type' => 'text', 'data' => ['key' => 'name', 'label' => 'Name']],
                    ['type' => 'text', 'data' => ['key' => 'manufacturer', 'label' => 'Hersteller']],
                ],
                'record_entity' => 'asset',
                'record_subject_attribute' => 'property_id',
                'record_mapping' => [['field' => 'name', 'attribute' => 'name'], ['field' => 'manufacturer', 'attribute' => 'manufacturer']],
                'record_defaults' => ['asset_type_id' => $assetType->getKey()],
            ]],
        ],
    ]]]);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])
        ->fillForm(['subject_id' => $property->getKey(), 'data' => ['brandschutz' => ['loescher' => [
            ['name' => 'FL 1', 'manufacturer' => 'Minimax'],
            ['name' => 'FL 2', 'manufacturer' => 'Gloria'],
        ]]]])
        ->call('submit')
        ->assertHasNoFormErrors();

    $submission = FormSubmission::query()->firstOrFail();
    $assets = Asset::query()->where('property_id', $property->getKey())->orderBy('id')->get();

    expect($assets)->toHaveCount(2)
        ->and($assets[0]->name)->toBe('FL 1')
        ->and($assets[0]->asset_type_id)->toBe($assetType->getKey())
        ->and($assets[0]->organization_id)->toBe(defaultOrganization()->getKey())
        ->and($submission->records()->count())->toBe(2);

    Livewire::withQueryParams(['submission' => $submission->getKey()])
        ->test(FillFormPage::class, ['form' => 'inspektion'])
        ->fillForm(['data' => ['brandschutz' => ['loescher' => [['name' => 'FL 1 neu', 'manufacturer' => 'Minimax']]]]])
        ->call('submit')
        ->assertHasNoFormErrors();

    expect(Asset::query()->where('property_id', $property->getKey())->pluck('name')->all())->toBe(['FL 1 neu'])
        ->and($submission->records()->count())->toBe(1);
});

it('renders tabs and wizard layouts and locks published keys', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm(['settings' => ['layout' => 'wizard']]);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])->assertOk()->assertSee('Außenanlagen');

    $form->update(['settings' => ['layout' => 'tabs']]);
    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])->assertOk()->assertSee('Außenanlagen');

    expect($form->fresh()->publishedKeys())->toContain('aussenanlagen', 'klingel', 'werbepylon', 'anzahl')
        ->and($form->fresh()->isKeyPublished('gibt_es_nicht'))->toBeFalse();
});

it('renders signatures as images in the PDF and shows configured table columns', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm(['schema' => [[
        'key' => 'abschluss',
        'label' => 'Abschluss',
        'fields' => [['type' => 'signature', 'data' => ['key' => 'unterschrift', 'label' => 'Unterschrift']]],
    ]]]);
    $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    $submission = FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'submitted_by' => auth()->id(), 'data' => ['abschluss' => ['unterschrift' => $dataUri]], 'submitted_at' => now()]);

    $html = view('form-builder::pdf.submission', ['submission' => $submission, 'form' => $form, 'presenter' => SubmissionPresenter::make($submission)])->render();

    expect($html)->toContain('<img src="data:image/png;base64');
});

it('notifies the submitter and additional recipients by mail', function () {
    Notification::fake();
    $admin = loginAsAdminInFmPanel();
    $form = publishedForm(['settings' => ['notify_submitter' => true, 'notify_emails' => 'fm@example.test, chef@example.test']]);

    Livewire::test(FillFormPage::class, ['form' => 'inspektion'])
        ->fillForm(['subject_id' => Property::factory()->create()->getKey(), 'data' => ['aussenanlagen' => ['klingel' => 'Ja']]])
        ->call('submit')
        ->assertHasNoFormErrors();

    Notification::assertSentTo($admin, SubmissionReceived::class, function ($notification, array $channels, $notifiable) use ($admin): bool {
        $mail = $notification->toMail($admin);

        return in_array('mail', $channels, true)
            && str_contains($mail->subject, 'Inspektion')
            && count($mail->rawAttachments) === 1;
    });
    Notification::assertSentOnDemand(SubmissionReceived::class, fn ($notification, array $channels, $notifiable): bool => $notifiable->routes['mail'] === ['fm@example.test', 'chef@example.test']);
});

it('exports submissions as CSV and round-trips a form template', function () {
    loginAsAdminInFmPanel();
    $form = publishedForm();
    FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'submitted_by' => auth()->id(), 'data' => ['aussenanlagen' => ['klingel' => 'Ja', 'klingel_position' => 'links', 'muelltonnen' => [['art' => 'Papier', 'anzahl' => 2]]]], 'submitted_at' => now()]);

    $csv = SubmissionCsvExporter::build($form, FormSubmission::query()->with(['submitter', 'subject', 'version'])->get());

    expect($csv)->toContain('Außenanlagen › Klingel')
        ->and($csv)->toContain(';Ja;')
        ->and($csv)->toContain('Zeile 1: Art = Papier, Anzahl = 2');

    Livewire::test(FormSubmissionsPage::class, ['form' => 'inspektion'])
        ->callAction('csv')
        ->assertFileDownloaded('inspektion-eingaben.csv');

    $template = FormTemplate::export($form);
    $copy = FormTemplate::import(json_decode(json_encode($template), true));

    expect($copy->slug)->not->toBe('inspektion')
        ->and($copy->slug)->toStartWith('inspektion-')
        ->and($copy->status)->toBe(FormStatus::Draft)
        ->and($copy->schema)->toBe($form->activeSchema())
        ->and($copy->tenants()->count())->toBe(0);

    expect(fn () => FormTemplate::import(['name' => 'x']))->toThrow(InvalidArgumentException::class);
});

it('duplicates a form as draft copy without submissions', function () {
    loginAsAdminInAdminPanel();
    $form = publishedForm(['tenants' => []]);
    FormSubmission::query()->create(['form_id' => $form->getKey(), 'form_version_id' => $form->published_version_id, 'data' => [], 'submitted_at' => now()]);

    Livewire::test(ListForms::class)
        ->callTableAction('replicate', $form)
        ->assertHasNoTableActionErrors();

    $copy = Form::query()->withoutGlobalScopes()->where('slug', '!=', 'inspektion')->firstOrFail();

    expect($copy->name)->toBe('Inspektion (Kopie)')
        ->and($copy->status)->toBe(FormStatus::Draft)
        ->and($copy->published_version_id)->toBeNull()
        ->and($copy->submissions()->count())->toBe(0)
        ->and($copy->schema)->toBe($form->schema);
});
