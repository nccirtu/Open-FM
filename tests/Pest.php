<?php

use App\Http\Middleware\ApplyTenantScopes;
use App\Models\ApiToken;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use App\Services\Waste\WasteLookupDefaults;
use App\Support\Plugins\PluginManager;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils as ShieldUtils;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function requiresPlugin(string ...$slugs): void
{
    foreach ($slugs as $slug) {
        if (! app(PluginManager::class)->installed($slug)) {
            test()->markTestSkipped("Plugin '{$slug}' ist nicht installiert.");
        }
    }
}

function defaultOrganization(): Organization
{
    return Organization::query()->where('slug', 'demo')->firstOrFail();
}

function inOrganization(Organization $organization, Closure $callback): mixed
{
    $previous = Filament\Facades\Filament::getTenant();

    Filament\Facades\Filament::setTenant($organization, isQuiet: true);
    setPermissionsTeamId($organization->getKey());

    try {
        return $callback();
    } finally {
        Filament\Facades\Filament::setTenant($previous, isQuiet: true);
        setPermissionsTeamId($previous?->getKey());
    }
}

function loginAsAdminInFmPanel(): User
{
    $user = loginAsAdmin();

    Filament\Facades\Filament::setCurrentPanel('fm');
    Filament\Facades\Filament::getPanel('fm')->boot();

    return $user;
}

function loginAsAdminInWastePanel(): User
{
    $user = loginAsAdmin();

    Filament\Facades\Filament::setCurrentPanel('waste');
    Filament\Facades\Filament::getPanel('waste')->boot();

    app(WasteLookupDefaults::class)->seed(defaultOrganization());

    return $user;
}

function loginAsAdminInEnergyPanel(): User
{
    $user = loginAsAdmin();

    Filament\Facades\Filament::setCurrentPanel('energy');
    Filament\Facades\Filament::getPanel('energy')->boot();

    return $user;
}

function loginAsAdminInContractsPanel(): User
{
    $user = loginAsAdmin();

    Filament\Facades\Filament::setCurrentPanel('contracts');
    Filament\Facades\Filament::getPanel('contracts')->boot();

    return $user;
}

function loginAsAdminInAdminPanel(): User
{
    $user = loginAsAdmin();

    Filament\Facades\Filament::setTenant(null);
    Filament\Facades\Filament::setCurrentPanel('admin');
    Filament\Facades\Filament::getPanel('admin')->boot();

    return $user;
}

function provisionRolesFor(Organization $organization): void
{
    static $permissionNames = null;

    if (Permission::query()->doesntExist()) {
        if ($permissionNames === null) {
            collect(FilamentShield::getResources())
                ->each(fn (array $entity) => ShieldUtils::generateForResource($entity['resourceFqcn']));

            $permissionNames = Permission::query()->pluck('name')->all();
        } else {
            Permission::query()->insert(array_map(
                fn (string $name): array => ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
                $permissionNames,
            ));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    if (! Role::query()->where('organization_id', $organization->getKey())->where('name', 'Admin')->exists()) {
        app(OrganizationRoleProvisioner::class)->provision($organization);
    }
}

/**
 * @param  array<int, string>  $abilities
 */
function actingAsApiUser(array $abilities, string $role = 'Admin', ?Organization $organization = null): User
{
    $organization ??= Filament\Facades\Filament::getTenant() ?? Organization::factory()->create();

    provisionRolesFor($organization);

    $user = User::factory()->create();
    $user->organizations()->syncWithoutDetaching([$organization->getKey()]);

    setPermissionsTeamId($organization->getKey());
    $user->assignRole(Role::query()->where('organization_id', $organization->getKey())->where('name', $role)->firstOrFail());

    return authenticateApiUser($user, $abilities, $organization);
}

/**
 * @param  array<int, string>  $abilities
 */
function authenticateApiUser(User $user, array $abilities, ?Organization $organization): User
{
    $token = (new ApiToken)->forceFill([
        'name' => 'test',
        'abilities' => $abilities,
        'organization_id' => $organization?->getKey(),
    ]);

    $user->withAccessToken($token);
    $user->wasRecentlyCreated = false;

    app('auth')->guard('sanctum')->setUser($user);
    app('auth')->shouldUse('sanctum');

    return $user;
}

function loginAsAdmin(string $email = 'tester@example.com'): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $user = User::factory()->create(['email' => $email]);
    $user->organizations()->syncWithoutDetaching([$organization->getKey()]);
    $user->assignRole('Admin');

    test()->actingAs($user);

    Filament\Facades\Filament::setTenant($organization);
    Filament\Facades\Filament::setCurrentPanel('pm');
    Filament\Facades\Filament::bootCurrentPanel();

    return $user;
}

function loginAsSuperAdmin(): User
{
    $user = loginAsAdmin('super@example.com');
    $user->forceFill(['is_super_admin' => true])->save();

    return $user;
}

function loginAsSuperAdminInAdminPanel(): User
{
    $user = loginAsAdminInAdminPanel();
    $user->forceFill(['is_super_admin' => true])->save();

    return $user;
}

function withoutTenantScopesApplied(): void
{
    foreach (ApplyTenantScopes::TENANT_MODELS as $model) {
        $model::addGlobalScope('organization', fn (Builder $query): Builder => $query);
    }
}

function withoutTenantScopes(Closure $callback): mixed
{
    $read = Closure::bind(static fn (): array => Model::$globalScopes, null, Model::class);
    $write = Closure::bind(static function (array $scopes): void {
        Model::$globalScopes = $scopes;
    }, null, Model::class);

    $saved = $read();
    $stripped = $saved;

    foreach (ApplyTenantScopes::TENANT_MODELS as $model) {
        unset($stripped[$model]);
    }

    $write($stripped);

    try {
        return $callback();
    } finally {
        $write($saved);
    }
}
