<?php

use App\Filament\Admin\Resources\OrganizationResource;
use App\Filament\Admin\Resources\OrganizationResource\Pages\ListOrganizations;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\CreateSlaDefinition;
use App\Filament\Admin\Resources\UserResource;
use App\Filament\Admin\Resources\UserResource\Pages\EditUser;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Http\Middleware\ScopeAdminPanelToAdministeredOrganizations;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use Livewire\Livewire;

/**
 * @return array{admin: User, own: Organization, other: Organization, ownUser: User, otherUser: User, sharedUser: User, superAdmin: User}
 */
function adminIsolationFixture(): array
{
    $admin = loginAsAdminInAdminPanel();
    $own = defaultOrganization();
    $other = Organization::factory()->create(['name' => 'Fremde Firma', 'slug' => 'fremde-firma']);
    app(OrganizationRoleProvisioner::class)->provision($other);

    $ownUser = User::factory()->create(['name' => 'Eigener Kollege', 'email' => 'eigener@example.com']);
    $ownUser->organizations()->attach($own->getKey());

    $otherUser = User::factory()->create(['name' => 'Fremder Kollege', 'email' => 'fremder@example.com']);
    $otherUser->organizations()->attach($other->getKey());

    $sharedUser = User::factory()->create(['name' => 'Geteilter Kollege', 'email' => 'geteilt@example.com']);
    $sharedUser->organizations()->attach([$own->getKey(), $other->getKey()]);

    $superAdmin = User::factory()->create(['name' => 'Betreiber', 'email' => 'betreiber@example.com']);
    $superAdmin->forceFill(['is_super_admin' => true])->save();
    $superAdmin->organizations()->attach($own->getKey());

    return compact('admin', 'own', 'other', 'ownUser', 'otherUser', 'sharedUser', 'superAdmin');
}

it('lists only users of administered organizations and hides super admins', function () {
    $fixture = adminIsolationFixture();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$fixture['ownUser'], $fixture['sharedUser']])
        ->assertCanNotSeeTableRecords([$fixture['otherUser'], $fixture['superAdmin']]);
});

it('lets super admins see all users', function () {
    $fixture = adminIsolationFixture();
    $fixture['admin']->forceFill(['is_super_admin' => true])->save();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$fixture['ownUser'], $fixture['otherUser'], $fixture['sharedUser'], $fixture['superAdmin']]);
});

it('only allows editing users that live entirely inside administered organizations', function () {
    $fixture = adminIsolationFixture();

    expect(UserResource::canEdit($fixture['ownUser']))->toBeTrue()
        ->and(UserResource::canEdit($fixture['sharedUser']))->toBeFalse()
        ->and(UserResource::canEdit($fixture['otherUser']))->toBeFalse()
        ->and(UserResource::canEdit($fixture['superAdmin']))->toBeFalse();

    Livewire::test(EditUser::class, ['record' => $fixture['sharedUser']->getKey()])->assertForbidden();
});

it('hides sensitive user actions for users the admin may not manage', function () {
    $fixture = adminIsolationFixture();

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('gdprExport', $fixture['ownUser'])
        ->assertTableActionHidden('gdprExport', $fixture['sharedUser'])
        ->assertTableActionHidden('anonymize', $fixture['sharedUser']);
});

it('only offers administered organizations and their roles when inviting', function () {
    $fixture = adminIsolationFixture();

    Livewire::test(ListUsers::class)
        ->callAction('invite', data: [
            'name' => 'Eingeschleuster Admin',
            'email' => 'eingeschleust@example.com',
            'organizations' => [$fixture['other']->getKey()],
            'role' => 'Admin',
        ])
        ->assertHasActionErrors();

    expect(User::query()->where('email', 'eingeschleust@example.com')->exists())->toBeFalse();
});

it('lists only administered organizations and denies updates to foreign ones', function () {
    $fixture = adminIsolationFixture();

    Livewire::test(ListOrganizations::class)
        ->assertCanSeeTableRecords([$fixture['own']])
        ->assertCanNotSeeTableRecords([$fixture['other']]);

    expect($fixture['admin']->can('update', $fixture['own']))->toBeTrue()
        ->and($fixture['admin']->can('update', $fixture['other']))->toBeFalse()
        ->and(OrganizationResource::canEdit($fixture['other']))->toBeFalse();
});

it('rejects SLA definitions for organizations the admin does not administer', function () {
    $fixture = adminIsolationFixture();

    Livewire::test(CreateSlaDefinition::class)
        ->fillForm(['organization_id' => $fixture['other']->getKey(), 'priority' => 'p1', 'response_hours' => 2, 'resolution_hours' => 8])
        ->call('create')
        ->assertHasFormErrors(['organization_id']);
});

it('keeps workflows, roles and app settings for super admins', function () {
    $fixture = adminIsolationFixture();

    $this->get('/admin/workflows')->assertForbidden();
    $this->get('/admin/shield/roles')->assertForbidden();
    $this->get('/admin/manage-app-settings')->assertForbidden();

    $fixture['admin']->forceFill(['is_super_admin' => true])->save();

    $this->get('/admin/workflows')->assertOk();
});

it('scopes tenant records in the admin panel to administered organizations', function () {
    $fixture = adminIsolationFixture();

    $this->get('/admin/users')->assertOk()
        ->assertSee('eigener@example.com')
        ->assertSee('geteilt@example.com')
        ->assertDontSee('fremder@example.com')
        ->assertDontSee('betreiber@example.com');
});

it('applies the admin scope middleware to Livewire requests as well', function () {
    loginAsAdminInAdminPanel();

    expect(Filament\Facades\Filament::getPanel('admin')->getAuthMiddleware())->toContain(ScopeAdminPanelToAdministeredOrganizations::class)
        ->and(Livewire::getPersistentMiddleware())->toContain(ScopeAdminPanelToAdministeredOrganizations::class);
});
