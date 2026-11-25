<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationRoleProvisioner;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => 'demo'],
            ['name' => 'Demo'],
        );

        setPermissionsTeamId($organization->getKey());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        collect(FilamentShield::getResources())
            ->each(fn (array $entity) => Utils::generateForResource($entity['resourceFqcn']));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(OrganizationRoleProvisioner::class)->provision($organization);

        $userRole = Role::query()
            ->where('name', 'User')
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        User::query()
            ->orderBy('id')
            ->get()
            ->each(function (User $bestehenderUser) use ($organization, $userRole): void {
                if (! $bestehenderUser->organizations()->whereKey($organization)->exists()) {
                    $bestehenderUser->organizations()->attach($organization);
                }

                if ($bestehenderUser->roles()->count() === 0) {
                    $bestehenderUser->assignRole($userRole);
                }
            });
    }
}
