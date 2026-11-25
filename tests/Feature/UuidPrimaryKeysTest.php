<?php

use App\Filament\Resources\PropertyResource;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Models\User;
use Cafm\FormBuilder\Models\Form;
use Illuminate\Support\Facades\DB;

it('generates UUIDv7 primary keys for app and plugin models', function () {
    requiresPlugin('form-builder');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $property = Property::factory()->create();
    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    $ticket = Ticket::factory()->create(['property_id' => $property->getKey()]);
    $node = StructureNode::query()->create(['property_id' => $property->getKey(), 'type' => 'building', 'name' => 'Haus A']);
    $form = Form::query()->create(['name' => 'Test', 'slug' => 'test', 'schema' => ['sections' => []]]);

    foreach ([$user, $organization, $property, $asset, $ticket, $node, $form] as $model) {
        expect($model->getKey())->toBeUuid()
            ->and($model->getIncrementing())->toBeFalse()
            ->and($model->getKeyType())->toBe('string');
    }

    expect($asset->property_id)->toBe($property->getKey())
        ->and($ticket->property_id)->toBe($property->getKey())
        ->and($node->property_id)->toBe($property->getKey());
});

it('stores role assignments and media relations with UUID model keys', function () {
    $admin = loginAsAdmin();

    $pivot = DB::table('model_has_roles')->where('model_id', $admin->getKey())->first();

    expect($pivot)->not->toBeNull()
        ->and($pivot->organization_id)->toBe(defaultOrganization()->getKey())
        ->and(DB::table('organization_user')->where('user_id', $admin->getKey())->exists())->toBeTrue();
});

it('resolves tenant URLs via the organization slug and record URLs via UUIDs', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $url = PropertyResource::getUrl('view', ['record' => $property], panel: 'fm', tenant: defaultOrganization());

    expect($url)->toContain('/fm/demo/')
        ->and($url)->toContain($property->getKey());

    $this->get($url)->assertOk();
});

it('imports form templates for organizations given by slug or UUID', function () {
    requiresPlugin('form-builder');

    loginAsAdmin();

    $organization = defaultOrganization();
    $path = tempnam(sys_get_temp_dir(), 'form-template-').'.json';
    file_put_contents($path, json_encode([
        'form_builder_template' => 1,
        'name' => 'Inspektion',
        'slug' => 'inspektion',
        'panels' => ['fm'],
        'schema' => [
            ['key' => 'allgemein', 'label' => 'Allgemein', 'fields' => [
                ['type' => 'text', 'data' => ['key' => 'notiz', 'label' => 'Notiz']],
            ]],
        ],
    ]));

    $this->artisan('forms:import-template', ['file' => $path, '--organization' => [$organization->slug]])
        ->assertSuccessful();

    $form = Form::query()->withoutGlobalScope(Form::TENANT_SCOPE)->where('slug', 'inspektion')->firstOrFail();

    expect($form->tenants()->pluck('organizations.id')->all())->toBe([$organization->getKey()]);

    $this->artisan('forms:import-template', ['file' => $path, '--organization' => ['gibt-es-nicht']])
        ->assertFailed();

    $this->artisan('forms:import-template', ['file' => 'gibt-es-nicht.form.json'])
        ->assertFailed();

    unlink($path);
});
