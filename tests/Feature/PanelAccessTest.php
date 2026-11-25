<?php

use App\Http\Middleware\SetLocale;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

function makeUserWithRole(string $roleName): User
{
    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => fake()->unique()->userName().'@example.com']);
    $user->organizations()->attach($organization);

    $role = Role::query()
        ->where('name', $roleName)
        ->where('organization_id', $organization->getKey())
        ->firstOrFail();

    $user->assignRole($role);

    return $user;
}

it('grants panel access based on roles', function (string $roleName, array $accessiblePanels) {
    test()->seed(RolesAndPermissionsSeeder::class);

    $user = makeUserWithRole($roleName);

    foreach (['admin', 'fm', 'contracts', 'pm', 'portal'] as $panelId) {
        expect($user->canAccessPanel(Filament::getPanel($panelId)))
            ->toBe(in_array($panelId, $accessiblePanels), "Panel [$panelId] für Rolle [$roleName]");
    }
})->with([
    'Admin' => ['Admin', ['admin', 'fm', 'contracts', 'pm', 'portal']],
    'User' => ['User', ['pm']],
    'FM' => ['FM', ['fm']],
    'PM' => ['PM', ['pm']],
    'Melder' => ['Melder', ['portal']],
    'Verträge' => ['Verträge', ['contracts']],
    'Dienstleister' => ['Dienstleister', ['fm']],
]);

it('denies all panels to users without any role', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();

    foreach (['admin', 'fm', 'contracts', 'pm', 'portal'] as $panelId) {
        expect($user->canAccessPanel(Filament::getPanel($panelId)))->toBeFalse();
    }
});

it('logs out users whose email domain no organization allows', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();
    $organization->update(['allowed_email_domains' => ['example.com']]);

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => 'fremd@fremde-domain.test', 'email_verified_at' => now()]);
    $user->organizations()->attach($organization);
    $user->assignRole('Admin');

    $this->actingAs($user)
        ->get('/pm')
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('allows logins when one of the user organizations permits the domain', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    defaultOrganization()->update(['allowed_email_domains' => ['example.com']]);

    $offeneOrganisation = Organization::factory()->create();

    setPermissionsTeamId($offeneOrganisation->getKey());

    $user = User::factory()->create(['email' => 'extern@example.com', 'email_verified_at' => now()]);
    $user->organizations()->attach($offeneOrganisation);

    app(OrganizationRoleProvisioner::class)->provision($offeneOrganisation);
    setPermissionsTeamId($offeneOrganisation->getKey());
    $user->assignRole(Role::query()->where('name', 'User')->where('organization_id', $offeneOrganisation->getKey())->firstOrFail());

    $this->actingAs($user)
        ->get('/pm')
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('sets the application locale from the tenant default locale', function () {
    $organization = Organization::factory()->create(['default_locale' => 'en']);

    inOrganization($organization, function (): void {
        $middleware = new SetLocale;

        $middleware->handle(
            Request::create('/'),
            fn ($request) => new Response,
        );

        expect(app()->getLocale())->toBe('en');
    });
});
