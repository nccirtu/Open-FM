<?php

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\PropertyResource;
use App\Filament\Resources\PropertyResource\Pages\CreateProperty;
use App\Filament\Resources\PropertyResource\Pages\EditProperty;
use App\Filament\Resources\PropertyResource\Pages\ListProperties;
use App\Models\Project;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * @param  list<string>  $revoked
 */
function userWithoutPermissions(array $revoked): User
{
    Role::findByName('User')->revokePermissionTo($revoked);

    $user = User::factory()->create();
    $user->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
    $user->assignRole('User');

    return $user;
}

it('seeds the own/all permissions and grants them to the standard roles', function () {
    loginAsAdmin();

    foreach (['property', 'project'] as $subject) {
        foreach (['view', 'create', 'update', 'delete'] as $ability) {
            expect(Permission::query()->where('name', "{$ability}_all_{$subject}")->exists())->toBeTrue()
                ->and(Role::findByName('User')->hasPermissionTo("{$ability}_all_{$subject}"))->toBeTrue()
                ->and(Role::findByName('Melder')->hasPermissionTo("{$ability}_all_{$subject}"))->toBeFalse();
        }
    }
});

it('restricts properties to the managed ones without view_all_property', function () {
    loginAsAdmin();
    $user = userWithoutPermissions(['view_all_property', 'update_all_property', 'delete_all_property']);
    $own = Property::factory()->managedBy($user)->create();
    $foreign = Property::factory()->create();

    expect($user->can('view', $own))->toBeTrue()
        ->and($user->can('update', $own))->toBeTrue()
        ->and($user->can('delete', $own))->toBeTrue()
        ->and($user->can('view', $foreign))->toBeFalse()
        ->and($user->can('update', $foreign))->toBeFalse()
        ->and($user->can('delete', $foreign))->toBeFalse();

    $this->actingAs($user);

    Livewire::test(ListProperties::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);

    expect(PropertyResource::getEloquentQuery()->pluck('id')->all())->toBe([$own->getKey()]);

    $this->get(PropertyResource::getUrl('view', ['record' => $foreign]))->assertNotFound();
    $this->get(PropertyResource::getUrl('view', ['record' => $own]))->assertSuccessful();
});

it('lets users with the all-permissions see every property and switch between mine and all', function () {
    $admin = loginAsAdmin();
    $own = Property::factory()->managedBy($admin)->create();
    $foreign = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->assertSet('activeTab', 'mine')
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$own, $foreign]);
});

it('opens the all tab when the user manages no property yet', function () {
    loginAsAdmin();
    $foreign = Property::factory()->create();

    Livewire::test(ListProperties::class)
        ->assertSet('activeTab', 'all')
        ->assertCanSeeTableRecords([$foreign]);
});

it('hides the mine/all tabs for users who only see their own properties', function () {
    loginAsAdmin();
    $user = userWithoutPermissions(['view_all_property']);
    $this->actingAs($user);

    $page = Livewire::test(ListProperties::class);

    expect($page->instance()->getTabs())->toBe([]);
});

it('makes the creator the property manager without create_all_property', function () {
    loginAsAdmin();
    $user = userWithoutPermissions(['create_all_property']);
    $other = User::factory()->create();
    $other->organizations()->attach(defaultOrganization());
    $this->actingAs($user);

    Livewire::test(CreateProperty::class)
        ->assertFormFieldDisabled('manager_id')
        ->fillForm(['property_number' => '7001', 'manager_id' => $other->getKey()])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Property::query()->where('property_number', '7001')->value('manager_id'))->toBe($user->getKey());
});

it('lets users with create_all_property choose another manager', function () {
    loginAsAdmin();
    $other = User::factory()->create();
    $other->organizations()->attach(defaultOrganization());

    Livewire::test(CreateProperty::class)
        ->assertFormFieldEnabled('manager_id')
        ->fillForm(['property_number' => '7002', 'manager_id' => $other->getKey()])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Property::query()->where('property_number', '7002')->value('manager_id'))->toBe($other->getKey());
});

it('keeps the manager unchanged when editing without update_all_property', function () {
    loginAsAdmin();
    $user = userWithoutPermissions(['update_all_property']);
    $other = User::factory()->create();
    $other->organizations()->attach(defaultOrganization());
    $property = Property::factory()->managedBy($user)->create();
    $this->actingAs($user);

    Livewire::test(EditProperty::class, ['record' => $property->getKey()])
        ->assertFormFieldDisabled('manager_id')
        ->set('data.manager_id', $other->getKey())
        ->set('data.city', 'Bonn')
        ->call('save')
        ->assertHasNoFormErrors();

    expect($property->fresh())
        ->manager_id->toBe($user->getKey())
        ->city->toBe('Bonn');
});

it('skips foreign properties when bulk deleting without delete_all_property', function () {
    loginAsAdmin();
    $user = userWithoutPermissions(['delete_all_property']);
    $own = Property::factory()->managedBy($user)->create();
    $foreign = Property::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListProperties::class)
        ->set('activeTab', 'all')
        ->callTableBulkAction('delete', [$own, $foreign]);

    expect(Property::query()->whereKey($own->getKey())->exists())->toBeFalse()
        ->and(Property::query()->whereKey($foreign->getKey())->exists())->toBeTrue();
});

it('restricts projects to the managed ones and offers mine/all tabs', function () {
    $admin = loginAsAdmin();
    $own = Project::factory()->managedBy($admin)->create();
    $foreign = Project::factory()->create();

    Livewire::test(ListProjects::class)
        ->assertSet('activeTab', 'mine')
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign])
        ->set('activeTab', 'all')
        ->assertCanSeeTableRecords([$own, $foreign]);

    $user = userWithoutPermissions(['view_all_project', 'update_all_project']);
    $managed = Project::factory()->managedBy($user)->create();
    $this->actingAs($user);

    expect($user->can('view', $managed))->toBeTrue()
        ->and($user->can('update', $managed))->toBeTrue()
        ->and($user->can('view', $foreign))->toBeFalse()
        ->and($user->can('update', $foreign))->toBeFalse()
        ->and(ProjectResource::getEloquentQuery()->pluck('id')->all())->toBe([$managed->getKey()]);

    Livewire::test(ListProjects::class)
        ->assertCanSeeTableRecords([$managed])
        ->assertCanNotSeeTableRecords([$own, $foreign]);
});

it('defaults the project manager to the creator', function () {
    $admin = loginAsAdmin();

    Livewire::test(CreateProject::class)
        ->assertFormSet(['manager_id' => $admin->getKey()])
        ->fillForm(['name' => 'Dachsanierung'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Project::query()->where('name', 'Dachsanierung')->value('manager_id'))->toBe($admin->getKey());
});

it('grants the all-permissions to existing roles in the migration', function () {
    loginAsAdmin();
    $permissionIds = Permission::query()->where('name', 'like', '%\_all\_%')->pluck('id');
    DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
    Permission::query()->whereIn('id', $permissionIds)->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $migration = require database_path('migrations/2026_10_06_202949_grant_scope_permissions_for_properties_and_projects.php');
    $migration->up();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(Role::findByName('User')->hasPermissionTo('view_all_property'))->toBeTrue()
        ->and(Role::findByName('FM')->hasPermissionTo('delete_all_project'))->toBeTrue()
        ->and(Role::findByName('Melder')->permissions()->where('name', 'like', '%\_all\_%')->exists())->toBeFalse();
});

it('exposes and filters the property manager in the API', function () {
    loginAsAdmin();
    $manager = User::factory()->create();
    $manager->organizations()->attach(defaultOrganization());
    $managed = Property::factory()->managedBy($manager)->create();
    Property::factory()->create();

    actingAsApiUser(['properties:read', 'properties:write']);

    $this->getJson('/api/v1/properties?filter[manager_id]='.$manager->getKey().'&include=manager')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $managed->getKey())
        ->assertJsonPath('data.0.manager.id', $manager->getKey());

    $this->postJson('/api/v1/properties', ['property_number' => '8001', 'manager_id' => User::factory()->create()->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('manager_id');

    $this->postJson('/api/v1/properties', ['property_number' => '8002', 'manager_id' => $manager->getKey()])
        ->assertCreated()
        ->assertJsonPath('data.manager_id', $manager->getKey());
});
