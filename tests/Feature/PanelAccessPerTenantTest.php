<?php

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;

it('only offers organizations in which the user holds a role of the panel', function () {
    $admin = loginAsAdmin();

    $other = Organization::factory()->create(['name' => 'Zweite Firma', 'slug' => 'zweite-firma']);
    app(OrganizationRoleProvisioner::class)->provision($other);

    $user = User::factory()->create();
    $user->organizations()->attach([defaultOrganization()->getKey(), $other->getKey()]);

    setPermissionsTeamId(defaultOrganization()->getKey());
    $user->assignRole('FM');
    setPermissionsTeamId($other->getKey());
    $user->assignRole('Melder');
    setPermissionsTeamId(defaultOrganization()->getKey());

    $fm = Filament::getPanel('fm');
    $portal = Filament::getPanel('portal');

    expect($user->getTenants($fm)->pluck('slug')->all())->toBe([defaultOrganization()->slug])
        ->and($user->getTenants($portal)->pluck('slug')->all())->toBe(['zweite-firma'])
        ->and($user->canUsePanelInOrganization($fm, $other))->toBeFalse()
        ->and($user->canUsePanelInOrganization($portal, $other))->toBeTrue();

    Filament::setCurrentPanel('fm');

    expect($user->canAccessTenant(defaultOrganization()))->toBeTrue()
        ->and($user->canAccessTenant($other))->toBeFalse();

    expect($admin->canAccessTenant(defaultOrganization()))->toBeTrue();
});

it('rejects requests to a panel of an organization without a matching role', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $other = Organization::factory()->create(['name' => 'Zweite Firma', 'slug' => 'zweite-firma']);
    app(OrganizationRoleProvisioner::class)->provision($other);

    $user = User::factory()->create();
    $user->organizations()->attach([defaultOrganization()->getKey(), $other->getKey()]);

    setPermissionsTeamId(defaultOrganization()->getKey());
    $user->assignRole('FM');
    setPermissionsTeamId($other->getKey());
    $user->assignRole('Melder');

    $this->actingAs($user);

    $this->get('/fm/'.defaultOrganization()->slug)->assertSuccessful();
    $this->get('/fm/zweite-firma')->assertNotFound();
});
