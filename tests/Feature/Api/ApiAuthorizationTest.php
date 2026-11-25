<?php

use App\Filament\Resources\ApiTokenResource\Pages\ListApiTokens;
use App\Models\ApiToken;
use App\Models\Company;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\Role;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

/**
 * @param  array<int, string>  $permissions
 */
function customApiRole(string $name, array $permissions): Role
{
    $organization = Filament::getTenant();

    provisionRolesFor($organization);
    setPermissionsTeamId($organization->getKey());

    $role = Role::query()->create(['name' => $name, 'guard_name' => 'web', 'organization_id' => $organization->getKey()]);
    $role->givePermissionTo($permissions);

    return $role;
}

it('denies the user list without view_any_user despite the users:read ability', function () {
    $other = User::factory()->create();
    $other->organizations()->attach(Filament::getTenant());

    actingAsApiUser(['users:read'], 'User');

    $this->getJson('/api/v1/users')->assertForbidden();
    $this->getJson('/api/v1/users/'.$other->getKey())->assertForbidden();
});

it('denies resources without permission to reporters and service partners', function () {
    actingAsApiUser(['*'], 'Melder');

    foreach (['properties', 'companies', 'contracts', 'projects', 'tasks', 'maintenance-orders', 'maintenance-plans', 'meters', 'waste-containers', 'users', 'organization'] as $resource) {
        $this->getJson('/api/v1/'.$resource)->assertForbidden();
    }

    $this->getJson('/api/v1/tickets')->assertOk();
    $this->postJson('/api/v1/properties', ['property_number' => 'X-1'])->assertForbidden();

    actingAsApiUser(['*'], 'Dienstleister');

    foreach (['properties', 'assets', 'companies', 'contracts', 'projects', 'maintenance-plans', 'cost-entries'] as $resource) {
        $this->getJson('/api/v1/'.$resource)->assertForbidden();
    }

    $this->getJson('/api/v1/tickets')->assertOk();
    $this->getJson('/api/v1/maintenance-orders')->assertOk();
});

it('denies every collection and create endpoint to an owner without any permission', function () {
    customApiRole('Leer', []);

    actingAsApiUser(['*'], 'Leer');

    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with((string) $route->getName(), 'api.v1.') && ! str_contains($route->uri(), '{'));

    expect($routes->count())->toBeGreaterThan(20);

    $notForbidden = $routes
        ->filter(fn ($route): bool => $this->json($route->methods()[0], '/'.$route->uri(), [])->status() !== 403)
        ->map(fn ($route): string => $route->methods()[0].' '.$route->uri())
        ->values()
        ->all();

    expect($notForbidden)->toBe([]);
});

it('lists and changes only own properties and projects without the all permissions', function () {
    customApiRole('Eigene', [
        'view_any_property', 'view_property', 'update_property', 'delete_property', 'create_property',
        'view_any_project', 'view_project', 'update_project', 'view_any_task', 'view_task', 'update_task', 'create_task',
    ]);

    $user = actingAsApiUser(['properties:write', 'projects:write'], 'Eigene');
    $fremdeBetreuung = User::factory()->create();
    $fremdeBetreuung->organizations()->attach(Filament::getTenant());

    $own = Property::factory()->managedBy($user)->create();
    $foreign = Property::factory()->managedBy($fremdeBetreuung)->create();
    $ownProject = Project::factory()->managedBy($user)->create();
    $foreignProject = Project::factory()->managedBy($fremdeBetreuung)->create();
    $ownMilestone = $ownProject->milestones()->create(['name' => 'Eigen', 'due_date' => '2026-02-01']);
    $foreignMilestone = $foreignProject->milestones()->create(['name' => 'Fremd', 'due_date' => '2026-02-01']);

    $this->getJson('/api/v1/properties')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->getKey());
    $this->getJson('/api/v1/properties/'.$own->getKey())->assertOk();
    $this->getJson('/api/v1/properties/'.$foreign->getKey())->assertForbidden();
    $this->patchJson('/api/v1/properties/'.$foreign->getKey(), ['city' => 'X'])->assertForbidden();
    $this->deleteJson('/api/v1/properties/'.$foreign->getKey())->assertForbidden();
    $this->patchJson('/api/v1/properties/'.$own->getKey(), ['city' => 'Eigen', 'manager_id' => $fremdeBetreuung->getKey()])->assertOk();

    expect($own->fresh()->manager_id)->toBe($user->getKey())
        ->and($own->fresh()->city)->toBe('Eigen');

    $created = $this->postJson('/api/v1/properties', ['property_number' => 'N-1', 'manager_id' => $fremdeBetreuung->getKey()])->assertCreated()->json('data.id');

    expect(Property::query()->findOrFail($created)->manager_id)->toBe($user->getKey());

    $this->getJson('/api/v1/projects')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownProject->getKey());
    $this->getJson('/api/v1/projects/'.$foreignProject->getKey())->assertForbidden();
    $this->patchJson('/api/v1/projects/'.$foreignProject->getKey(), ['name' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/projects/'.$ownProject->getKey(), ['name' => 'Eigen'])->assertOk();

    $this->getJson('/api/v1/milestones')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownMilestone->getKey());
    $this->getJson('/api/v1/milestones/'.$foreignMilestone->getKey())->assertForbidden();
    $this->patchJson('/api/v1/milestones/'.$foreignMilestone->getKey(), ['name' => 'X'])->assertForbidden();
    $this->postJson('/api/v1/milestones', ['project_id' => $foreignProject->getKey(), 'name' => 'X', 'due_date' => '2026-03-01'])->assertForbidden();
    $this->postJson('/api/v1/milestones', ['project_id' => $ownProject->getKey(), 'name' => 'Neu', 'due_date' => '2026-03-01'])->assertCreated();
    $this->postJson('/api/v1/tasks', ['project_id' => $foreignProject->getKey(), 'title' => 'X'])->assertForbidden();
    $this->postJson('/api/v1/tasks', ['project_id' => $ownProject->getKey(), 'title' => 'Neu'])->assertCreated();

    $foreignTask = Task::factory()->create(['project_id' => $foreignProject->getKey()]);
    $assignedTask = Task::factory()->create(['project_id' => $foreignProject->getKey(), 'assignee_id' => $user->getKey()]);

    $this->getJson('/api/v1/tasks/'.$foreignTask->getKey())->assertForbidden();
    $this->getJson('/api/v1/tasks/'.$assignedTask->getKey())->assertOk();
    $this->getJson('/api/v1/tasks')->assertJsonCount(2, 'data');
});

it('lists every property and project with the all permissions', function () {
    customApiRole('Alle', ['view_any_property', 'view_property', 'view_all_property', 'view_any_project', 'view_project', 'view_all_project']);

    actingAsApiUser(['*'], 'Alle');

    $foreign = Property::factory()->managedBy(User::factory()->create())->create();
    $foreignProject = Project::factory()->managedBy(User::factory()->create())->create();

    $this->getJson('/api/v1/properties')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/properties/'.$foreign->getKey())->assertOk();
    $this->getJson('/api/v1/projects/'.$foreignProject->getKey())->assertOk();
    $this->patchJson('/api/v1/properties/'.$foreign->getKey(), ['city' => 'X'])->assertForbidden();
});

it('restricts service partners to the tickets and maintenance orders of their company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $own = Ticket::factory()->create(['company_id' => $company->getKey()]);
    $foreign = Ticket::factory()->create(['company_id' => $otherCompany->getKey()]);
    $ownOrder = MaintenanceOrder::factory()->create(['maintenance_plan_id' => MaintenancePlan::factory()->create(['company_id' => $company->getKey()])->getKey()]);
    $foreignOrder = MaintenanceOrder::factory()->create(['maintenance_plan_id' => MaintenancePlan::factory()->create(['company_id' => $otherCompany->getKey()])->getKey()]);

    $partner = actingAsApiUser(['*'], 'Dienstleister');
    $partner->forceFill(['company_id' => $company->getKey()])->save();

    $this->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->getKey());
    $this->getJson('/api/v1/tickets/'.$own->getKey())->assertOk();
    $this->getJson('/api/v1/tickets/'.$foreign->getKey())->assertForbidden();
    $this->patchJson('/api/v1/tickets/'.$foreign->getKey(), ['title' => 'X'])->assertForbidden();
    $this->patchJson('/api/v1/tickets/'.$own->getKey(), ['title' => 'Neu', 'company_id' => $otherCompany->getKey()])->assertOk();

    expect($own->fresh()->company_id)->toBe($company->getKey());

    $this->getJson('/api/v1/tickets/'.$foreign->getKey().'/replies')->assertForbidden();

    $this->getJson('/api/v1/maintenance-orders')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ownOrder->getKey());
    $this->getJson('/api/v1/maintenance-orders/'.$foreignOrder->getKey())->assertForbidden();
    $this->patchJson('/api/v1/maintenance-orders/'.$foreignOrder->getKey(), ['notes' => 'X'])->assertForbidden();
});

it('shows reporters only their own tickets and no internal notes', function () {
    $reporter = actingAsApiUser(['*'], 'Melder');

    $own = Ticket::factory()->create(['reported_by' => $reporter->getKey()]);
    $foreign = Ticket::factory()->create(['reported_by' => User::factory()->create()->getKey()]);
    $public = TicketReply::factory()->create(['ticket_id' => $own->getKey(), 'is_internal_note' => false]);
    $internal = TicketReply::factory()->create(['ticket_id' => $own->getKey(), 'is_internal_note' => true]);

    $this->getJson('/api/v1/tickets')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->getKey());
    $this->getJson('/api/v1/tickets/'.$foreign->getKey())->assertForbidden();
    $this->getJson('/api/v1/tickets/'.$own->getKey().'/replies')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->getKey());
    $this->getJson('/api/v1/replies/'.$internal->getKey())->assertForbidden();
    $this->getJson('/api/v1/replies/'.$public->getKey())->assertOk();

    $reply = $this->postJson('/api/v1/tickets/'.$own->getKey().'/replies', ['message' => 'Danke', 'is_internal_note' => true])->assertCreated();

    expect($reply->json('data.is_internal_note'))->toBeFalse();
});

it('never updates or deletes ticket replies through the API', function () {
    $reply = TicketReply::factory()->create(['message' => 'Alt']);

    actingAsApiUser(['tickets:write']);

    $this->patchJson('/api/v1/replies/'.$reply->getKey(), ['message' => 'Neu'])->assertForbidden();
    $this->deleteJson('/api/v1/replies/'.$reply->getKey())->assertForbidden();

    expect($reply->fresh()->message)->toBe('Alt');
});

it('lets only super admins write the global asset type catalog', function () {
    $admin = actingAsApiUser(['assets:read', 'assets:write']);

    $this->getJson('/api/v1/asset-types')->assertOk();
    $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertForbidden();

    $admin->forceFill(['is_super_admin' => true])->save();

    $id = $this->postJson('/api/v1/asset-types', ['name' => 'Pumpe'])->assertCreated()->json('data.id');
    $this->patchJson('/api/v1/asset-types/'.$id, ['name' => 'Umwälzpumpe'])->assertOk();
    $this->deleteJson('/api/v1/asset-types/'.$id)->assertNoContent();

    $admin->forceFill(['is_super_admin' => false])->save();
    $other = $this->postJson('/api/v1/asset-types', ['name' => 'Ventil'])->assertForbidden();
});

it('reads sla definitions and the organization for administrators only', function () {
    actingAsApiUser(['tickets:read', 'organization:read'], 'User');

    $this->getJson('/api/v1/sla-definitions')->assertForbidden();
    $this->getJson('/api/v1/organization')->assertForbidden();

    actingAsApiUser(['tickets:read', 'organization:read']);

    $this->getJson('/api/v1/sla-definitions')->assertOk();
    $this->getJson('/api/v1/organization')->assertOk();
});

it('rejects tokens that are not bound to an organization', function () {
    $user = User::factory()->create();
    $user->organizations()->attach(Filament::getTenant());

    authenticateApiUser($user, ['*'], null);

    $this->getJson('/api/v1/properties')
        ->assertForbidden()
        ->assertJsonPath('message', __('api.errors.token_without_organization'));
});

it('rejects tokens of owners who left the organization', function () {
    $user = actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/properties')->assertOk();

    $user->organizations()->detach(Filament::getTenant());

    $this->getJson('/api/v1/properties')
        ->assertForbidden()
        ->assertJsonPath('message', __('api.errors.token_organization_membership'));
});

it('rejects tokens of owners who were demoted to a role without permissions', function () {
    $user = actingAsApiUser(['properties:read']);

    $this->getJson('/api/v1/properties')->assertOk();

    setPermissionsTeamId(Filament::getTenant()->getKey());
    $user->syncRoles([Role::query()->where('organization_id', Filament::getTenant()->getKey())->where('name', 'Melder')->firstOrFail()]);

    $this->getJson('/api/v1/properties')->assertForbidden();
});

it('authenticates real bearer tokens bound to the organization and honours their expiry', function () {
    provisionRolesFor(Filament::getTenant());

    $user = User::factory()->create();
    $user->organizations()->attach(Filament::getTenant());
    setPermissionsTeamId(Filament::getTenant()->getKey());
    $user->assignRole('Admin');

    $token = $user->createToken('real', ['properties:read'], now()->addDay());

    expect($token->accessToken->organization_id)->toBe(Filament::getTenant()->getKey());

    $this->withToken($token->plainTextToken)->getJson('/api/v1/properties')->assertOk();

    $token->accessToken->forceFill(['expires_at' => now()->subMinute()])->save();
    Auth::forgetGuards();

    $this->withToken($token->plainTextToken)->getJson('/api/v1/properties')->assertUnauthorized();
});

it('rejects bearer tokens created without a tenant', function () {
    provisionRolesFor(Filament::getTenant());

    $user = User::factory()->create();
    $user->organizations()->attach(Filament::getTenant());
    setPermissionsTeamId(Filament::getTenant()->getKey());
    $user->assignRole('Admin');

    Filament::setTenant(null, isQuiet: true);

    $token = $user->createToken('ohne', ['properties:read']);

    expect($token->accessToken->organization_id)->toBeNull();

    $this->withToken($token->plainTextToken)->getJson('/api/v1/properties')->assertForbidden();
});

it('requires an expiry date when creating a token', function () {
    loginAsAdmin();

    Livewire::test(ListApiTokens::class)
        ->callAction('createToken', data: ['name' => 'Ohne Ablauf', 'abilities' => ['assets:read'], 'expires_at' => null])
        ->assertHasActionErrors(['expires_at' => 'required']);

    expect(ApiToken::query()->count())->toBe(0);
});

it('rejects an expiry date beyond the configured maximum lifetime', function () {
    config()->set('api.token_max_days', 30);
    loginAsAdmin();

    Livewire::test(ListApiTokens::class)
        ->callAction('createToken', data: ['name' => 'Zu lang', 'abilities' => ['assets:read'], 'expires_at' => now()->addDays(31)->format('Y-m-d H:i:s')])
        ->assertHasActionErrors(['expires_at']);

    expect(ApiToken::query()->count())->toBe(0);

    Livewire::test(ListApiTokens::class)
        ->callAction('createToken', data: ['name' => 'Passt', 'abilities' => ['assets:read'], 'expires_at' => now()->addDays(29)->format('Y-m-d H:i:s')])
        ->assertHasNoActionErrors();

    expect(ApiToken::query()->where('name', 'Passt')->firstOrFail()->expires_at->isFuture())->toBeTrue();
});

it('allows tokens without an expiry date when the maximum lifetime is unlimited', function () {
    config()->set('api.token_max_days', 0);
    loginAsAdmin();

    Livewire::test(ListApiTokens::class)
        ->callAction('createToken', data: ['name' => 'Unbegrenzt', 'abilities' => ['assets:read'], 'expires_at' => null])
        ->assertHasNoActionErrors();

    expect(ApiToken::query()->where('name', 'Unbegrenzt')->firstOrFail()->expires_at)->toBeNull();
});

it('keeps generating the OpenAPI documentation', function () {
    loginAsAdmin();

    $this->get('/docs/api.json')
        ->assertOk()
        ->assertJsonStructure(['paths' => ['/tickets', '/properties', '/waste-fill-readings', '/replies/{reply}']]);
});

it('hides e-mail addresses of included users from owners without user permissions', function () {
    $user = actingAsApiUser(['properties:read'], 'FM');
    $organization = $user->organizations()->firstOrFail();
    $manager = User::factory()->create(['name' => 'Betreuer Testmann', 'email' => 'betreuer@example.com']);
    $manager->organizations()->attach($organization->getKey());

    inOrganization($organization, fn () => Property::factory()->create(['manager_id' => $manager->getKey()]));

    $response = $this->getJson('/api/v1/properties?include=manager')->assertOk();

    expect(json_encode($response->json()))->toContain('Betreuer Testmann')
        ->not->toContain('betreuer@example.com');
});
