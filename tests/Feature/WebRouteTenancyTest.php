<?php

use App\Models\BimModel;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

function webUserWithRole(string $role): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create();
    $user->organizations()->attach($organization->getKey());
    $user->assignRole($role);

    test()->actingAs($user);

    return $user;
}

it('resolves route model bindings of web routes inside the user organizations', function () {
    webUserWithRole('Admin');

    $foreignOrganization = Organization::factory()->create();
    $foreignProperty = inOrganization($foreignOrganization, fn (): Property => Property::factory()->create());
    $foreignModel = inOrganization($foreignOrganization, fn (): BimModel => BimModel::factory()->create());

    withoutTenantScopesApplied();

    $this->get(route('property.dossier.pdf', $foreignProperty))->assertNotFound();

    withoutTenantScopesApplied();

    $this->get(route('bim-models.file', $foreignModel))->assertNotFound();

    withoutTenantScopesApplied();

    $this->postJson(route('bim-models.import', $foreignModel), ['rooms' => []])->assertNotFound();
});

it('requires property permissions for dossier and BIM routes', function () {
    webUserWithRole('Melder');

    $property = inOrganization(defaultOrganization(), fn (): Property => Property::factory()->create());
    $model = inOrganization(defaultOrganization(), fn (): BimModel => BimModel::factory()->create(['property_id' => $property->getKey()]));

    Storage::fake('local');
    Storage::disk('local')->put($model->file_path, 'IFC');

    $this->get(route('property.dossier.pdf', $property))->assertForbidden();
    $this->get(route('bim-models.file', $model))->assertForbidden();
    $this->postJson(route('bim-models.import', $model), ['rooms' => []])->assertForbidden();
});

it('limits the maintenance calendar to users who may view maintenance plans', function () {
    webUserWithRole('Melder');

    $this->get(route('maintenance.calendar.pdf'))->assertForbidden();
});

it('allows admins to download the dossier and the maintenance calendar', function () {
    webUserWithRole('Admin');

    $property = inOrganization(defaultOrganization(), fn (): Property => Property::factory()->create());

    $this->get(route('property.dossier.pdf', $property))->assertSuccessful();
    $this->get(route('maintenance.calendar.pdf'))->assertSuccessful();
});
