<?php

use App\Filament\Admin\Resources\OrganizationResource\Pages\CreateOrganization;
use App\Filament\Admin\Resources\OrganizationResource\Pages\ListOrganizations;
use App\Filament\Admin\Resources\SlaDefinitionResource;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\CreateSlaDefinition;
use App\Filament\Admin\Resources\SlaDefinitionResource\Pages\ListSlaDefinitions;
use App\Filament\Admin\Resources\TicketAutomationRuleResource;
use App\Filament\Admin\Resources\TicketAutomationRuleResource\Pages\ListTicketAutomationRules;
use App\Filament\Admin\Resources\UserResource\Pages\ListUsers;
use App\Models\Organization;
use App\Models\Role;
use App\Models\SlaDefinition;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('lists organizations in the admin panel', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(ListOrganizations::class)
        ->assertCanSeeTableRecords([defaultOrganization()]);
});

it('creates an organization with default roles from the admin panel', function () {
    loginAsAdminInAdminPanel();

    Livewire::test(CreateOrganization::class)
        ->fillForm([
            'name' => 'Neue Firma',
            'slug' => 'neue-firma',
            'default_locale' => 'en',
            'allowed_email_domains' => ['neuefirma.de'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->where('slug', 'neue-firma')->firstOrFail();

    expect($organization->default_locale)->toBe('en');
    expect($organization->allowed_email_domains)->toBe(['neuefirma.de']);

    expect(Role::query()->where('organization_id', $organization->getKey())->pluck('name')->sort()->values()->all())
        ->toBe(['Abfall', 'Admin', 'Dienstleister', 'FM', 'Melder', 'PM', 'User', 'Verträge']);
});

it('invites a user with organization membership, role and password link', function () {
    Notification::fake();

    loginAsAdminInAdminPanel();

    $organization = defaultOrganization();

    Livewire::test(ListUsers::class)
        ->callAction('invite', data: [
            'name' => 'Neue Kollegin',
            'email' => 'neue.kollegin@example.com',
            'organizations' => [$organization->getKey()],
            'role' => 'FM',
        ])
        ->assertHasNoActionErrors();

    $user = User::query()->where('email', 'neue.kollegin@example.com')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->organizations()->whereKey($organization)->exists())->toBeTrue();

    inOrganization($organization, function () use ($user): void {
        expect($user->fresh()->hasRole('FM'))->toBeTrue();
    });

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification): bool {
        return str_contains($notification->url, '/fm/password-reset/reset');
    });
});

it('sends portal reset links when inviting a Melder', function () {
    Notification::fake();

    loginAsAdminInAdminPanel();

    Livewire::test(ListUsers::class)
        ->callAction('invite', data: [
            'name' => 'Neuer Melder',
            'email' => 'neuer.melder@example.com',
            'organizations' => [defaultOrganization()->getKey()],
            'role' => 'Melder',
        ])
        ->assertHasNoActionErrors();

    $user = User::query()->where('email', 'neuer.melder@example.com')->firstOrFail();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification): bool {
        return str_contains($notification->url, '/portal/password-reset/reset');
    });
});

it('rejects invitations with an existing email', function () {
    Notification::fake();

    $admin = loginAsAdminInAdminPanel();

    Livewire::test(ListUsers::class)
        ->callAction('invite', data: [
            'name' => 'Doppelt',
            'email' => $admin->email,
            'organizations' => [defaultOrganization()->getKey()],
            'role' => 'User',
        ])
        ->assertHasActionErrors(['email' => 'unique']);

    Notification::assertNothingSent();
});

it('manages roles and permissions only in the admin panel', function () {
    loginAsSuperAdminInAdminPanel();

    $managesUsers = fn (string $panel): bool => collect(Filament::getPanel($panel)->getResources())->contains(fn (string $resource): bool => $resource::getModel() === User::class);

    expect(collect(Filament::getPanel('admin')->getResources())->contains(RoleResource::class))->toBeTrue()
        ->and(collect(Filament::getPanel('pm')->getResources())->contains(RoleResource::class))->toBeFalse()
        ->and(collect(Filament::getPanel('fm')->getResources())->contains(RoleResource::class))->toBeFalse()
        ->and($managesUsers('admin'))->toBeTrue()
        ->and($managesUsers('pm'))->toBeFalse()
        ->and($managesUsers('fm'))->toBeFalse();

    $organization = defaultOrganization();
    $other = Organization::factory()->create(['name' => 'Zweite Firma']);
    app(OrganizationRoleProvisioner::class)->provision($other);
    Filament::setTenant(null, isQuiet: true);

    $ownAdmin = Role::query()->where('organization_id', $organization->getKey())->where('name', 'Admin')->firstOrFail();
    $otherAdmin = Role::query()->where('organization_id', $other->getKey())->where('name', 'Admin')->firstOrFail();

    Livewire::test(ListRoles::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$ownAdmin, $otherAdmin])
        ->assertSee('Zweite Firma');

    Livewire::test(CreateRole::class)
        ->fillForm(['name' => 'Hausmeister', 'organization_id' => $other->getKey()])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Role::query()->where('name', 'Hausmeister')->sole()->organization_id)->toBe($other->getKey());
});

it('denies role management to everyone but super admins', function () {
    $admin = loginAsAdminInAdminPanel();

    $user = User::factory()->create();
    $user->organizations()->syncWithoutDetaching([defaultOrganization()->getKey()]);
    setPermissionsTeamId(defaultOrganization()->getKey());
    $user->assignRole('User');

    expect($user->can('viewAny', Role::class))->toBeFalse()
        ->and($admin->can('viewAny', Role::class))->toBeFalse();

    $admin->forceFill(['is_super_admin' => true])->save();

    expect($admin->can('viewAny', Role::class))->toBeTrue();
});

it('manages SLA rules and ticket automations only in the admin panel with an organization per rule', function () {
    loginAsAdminInAdminPanel();

    $adminResources = collect(Filament::getPanel('admin')->getResources());
    $fmResources = collect(Filament::getPanel('fm')->getResources());

    expect($adminResources->contains(SlaDefinitionResource::class))->toBeTrue()
        ->and($adminResources->contains(TicketAutomationRuleResource::class))->toBeTrue()
        ->and($fmResources->contains(SlaDefinitionResource::class))->toBeFalse()
        ->and($fmResources->contains(TicketAutomationRuleResource::class))->toBeFalse();

    $organization = defaultOrganization();

    Livewire::test(CreateSlaDefinition::class)
        ->fillForm(['organization_id' => $organization->getKey(), 'priority' => 'p1', 'response_hours' => 2, 'resolution_hours' => 8, 'is_active' => true])
        ->call('create')
        ->assertHasNoFormErrors();

    Livewire::test(CreateSlaDefinition::class)
        ->fillForm(['organization_id' => $organization->getKey(), 'priority' => 'p1', 'response_hours' => 4, 'resolution_hours' => 24])
        ->call('create')
        ->assertHasFormErrors(['priority']);

    expect(SlaDefinition::query()->where('organization_id', $organization->getKey())->count())->toBe(1);

    Livewire::test(ListSlaDefinitions::class)->assertOk()->assertSee($organization->name);
    Livewire::test(ListTicketAutomationRules::class)->assertOk();
});
