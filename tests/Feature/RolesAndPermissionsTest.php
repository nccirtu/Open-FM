<?php

use App\Filament\Pages\Auth\Register;
use App\Models\Asset;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

function makeRoleAsset(): Asset
{
    $property = Property::create(['property_number' => '4711', 'city' => 'Köln', 'country' => 'DE']);

    return Asset::create(['property_id' => $property->id, 'name' => 'Lift']);
}

it('seeds the Admin and User roles with the expected permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = Role::findByName('Admin');
    $user = Role::findByName('User');

    expect($admin->permissions()->count())->toBe(Permission::query()->count());
    expect($user->permissions()->count())->toBe(177);

    expect($user->hasPermissionTo('view_any_asset'))->toBeTrue();
    expect($user->hasPermissionTo('create_asset'))->toBeTrue();
    expect($user->hasPermissionTo('delete_asset'))->toBeTrue();
    expect($user->hasPermissionTo('view_any_role'))->toBeFalse();

    expect($user->hasPermissionTo('view_any_role'))->toBeFalse();
    expect($user->hasPermissionTo('view_any_user'))->toBeFalse();
    expect($user->hasPermissionTo('delete_asset'))->toBeTrue();
});

it('is idempotent when seeded twice', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->count())->toBe(8);
    expect(Role::query()->pluck('name')->sort()->values()->all())
        ->toBe(['Abfall', 'Admin', 'Dienstleister', 'FM', 'Melder', 'PM', 'User', 'Verträge']);
    expect(Role::findByName('User')->permissions()->count())->toBe(177);
    expect(Role::findByName('FM')->permissions()->count())->toBe(177);
    expect(Role::findByName('Melder')->permissions()->count())->toBe(5);
    expect(Role::findByName('Verträge')->permissions()->count())->toBe(6);
    expect(Role::findByName('Dienstleister')->permissions()->count())->toBe(11);
});

it('lets a User manage assets but not roles, users or organizations via policies', function () {
    loginAsAdmin();
    $user = User::factory()->create();
    $user->assignRole('User');
    $asset = makeRoleAsset();

    expect($user->can('viewAny', Asset::class))->toBeTrue()
        ->and($user->can('create', Asset::class))->toBeTrue()
        ->and($user->can('update', $asset))->toBeTrue()
        ->and($user->can('delete', $asset))->toBeTrue()
        ->and($user->can('viewAny', Role::class))->toBeFalse()
        ->and($user->can('viewAny', User::class))->toBeFalse();
});

it('allows an Admin to manage assets, and super admins to manage roles', function () {
    $admin = loginAsAdmin();
    $asset = makeRoleAsset();

    expect($admin->can('delete', $asset))->toBeTrue()
        ->and($admin->can('deleteAny', Asset::class))->toBeTrue()
        ->and($admin->can('viewAny', Role::class))->toBeFalse();

    $admin->forceFill(['is_super_admin' => true])->save();

    expect($admin->can('viewAny', Role::class))->toBeTrue();
});

it('assigns the User role to existing users without a role when seeding', function () {
    $bestand = User::factory()->create();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect($bestand->fresh()->hasRole('User'))->toBeTrue();
});

it('assigns a role via the app:assign-role command without a team context', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $organization = defaultOrganization();
    $user = User::factory()->unverified()->create(['email' => 'chef@example.com']);

    setPermissionsTeamId(null);

    $this->artisan('app:assign-role', ['email' => 'chef@example.com'])
        ->assertSuccessful();

    setPermissionsTeamId($organization->getKey());
    $user = $user->fresh();

    expect($user->organizations()->whereKey($organization->getKey())->exists())->toBeTrue()
        ->and($user->hasRole('Admin'))->toBeTrue()
        ->and($user->hasRole('User'))->toBeFalse()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

it('marks the email as verified with --verify-email', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->unverified()->create(['email' => 'neu@example.com']);

    $this->artisan('app:assign-role', ['email' => 'neu@example.com', '--verify-email' => true])
        ->assertSuccessful();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('assigns the role only in the organization given by slug', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $second = Organization::factory()->create(['slug' => 'zweite']);
    app(OrganizationRoleProvisioner::class)->provision($second);
    $user = User::factory()->create(['email' => 'zweit@example.com']);

    setPermissionsTeamId(null);

    $this->artisan('app:assign-role', ['email' => 'zweit@example.com', 'role' => 'FM', '--organization' => 'zweite'])
        ->assertSuccessful();

    expect($user->organizations()->pluck('organizations.id')->all())->toBe([$second->getKey()]);

    setPermissionsTeamId($second->getKey());
    expect($user->fresh()->hasRole('FM'))->toBeTrue();

    setPermissionsTeamId(defaultOrganization()->getKey());
    expect($user->fresh()->hasRole('FM'))->toBeFalse();
});

it('fails the command for unknown users, roles or organizations', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->artisan('app:assign-role', ['email' => 'gibtsnicht@example.com'])->assertFailed();

    User::factory()->create(['email' => 'da@example.com']);
    $this->artisan('app:assign-role', ['email' => 'da@example.com', 'role' => 'Superheld'])->assertFailed();
    $this->artisan('app:assign-role', ['email' => 'da@example.com', '--organization' => 'gibtsnicht'])->assertFailed();
});

it('asks for an organization when the user has none and several exist', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Organization::factory()->create();
    User::factory()->create(['email' => 'heimatlos@example.com']);

    $this->artisan('app:assign-role', ['email' => 'heimatlos@example.com'])->assertFailed();
});

it('assigns the User role on self-registration', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    Livewire\Livewire::test(Register::class)
        ->fillForm([
            'name' => 'Neuer Kollege',
            'email' => 'neu@example.com',
            'password' => 'geheim1234',
            'passwordConfirmation' => 'geheim1234',
        ])
        ->call('register');

    $neu = User::query()->where('email', 'neu@example.com')->firstOrFail();

    expect($neu->hasRole('User'))->toBeTrue();
});
