<?php

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Livewire\Topbar;
use Livewire\Livewire;

it('lets an Admin create a new tenant with a default locale from the tenant menu modal', function () {
    $admin = loginAsAdmin();

    Livewire::test(Topbar::class)
        ->callAction('registerOrganization', data: [
            'name' => 'Musterfirma GmbH',
            'default_locale' => 'en',
        ])
        ->assertRedirect();

    $organization = Organization::query()->where('slug', 'musterfirma-gmbh')->firstOrFail();

    expect($organization->name)->toBe('Musterfirma GmbH');
    expect($organization->default_locale)->toBe('en');
    expect($admin->fresh()->canAccessTenant($organization))->toBeTrue();

    inOrganization($organization, function () use ($admin): void {
        expect($admin->fresh()->hasRole('Admin'))->toBeTrue();
        expect($admin->fresh()->can('view_any_property'))->toBeTrue();
    });
});

it('generates unique slugs when tenant names collide', function () {
    loginAsAdmin();

    Livewire::test(Topbar::class)
        ->callAction('registerOrganization', data: ['name' => 'Doppelt', 'default_locale' => 'de'])
        ->assertRedirect();

    Livewire::test(Topbar::class)
        ->callAction('registerOrganization', data: ['name' => 'Doppelt', 'default_locale' => 'de'])
        ->assertRedirect();

    expect(Organization::query()->pluck('slug')->all())
        ->toContain('doppelt')
        ->toContain('doppelt-2');
});

it('requires a name and a default locale', function () {
    loginAsAdmin();

    Livewire::test(Topbar::class)
        ->callAction('registerOrganization', data: ['name' => '', 'default_locale' => null])
        ->assertHasActionErrors(['name' => 'required', 'default_locale' => 'required']);

    expect(Organization::query()->count())->toBe(1);
});

it('hides the tenant registration action from users without the Admin role', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => 'mitarbeiter@example.com']);
    $user->organizations()->attach($organization);
    $user->assignRole('User');

    test()->actingAs($user);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('pm');
    Filament::bootCurrentPanel();

    Livewire::test(Topbar::class)->assertActionDoesNotExist('registerOrganization');
});

it('shows the tenant registration action to Admins even without a permissions team context', function () {
    loginAsAdmin();

    setPermissionsTeamId(null);

    Livewire::test(Topbar::class)->assertActionVisible('registerOrganization');
});
