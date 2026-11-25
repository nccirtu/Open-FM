<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Role;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class OrganizationRoleProvisioner
{
    /**
     * @var list<string>
     */
    public const NUR_ADMIN_PERMISSIONS = [
        'view_role', 'view_any_role', 'create_role',
        'update_role', 'delete_role', 'delete_any_role',
        'view_user', 'view_any_user', 'create_user',
        'update_user', 'delete_user', 'delete_any_user',
        'view_organization', 'view_any_organization', 'create_organization',
        'update_organization', 'delete_organization', 'delete_any_organization',
        'view_form', 'view_any_form', 'create_form',
        'update_form', 'delete_form', 'delete_any_form',
        'view_custom::field::section', 'view_any_custom::field::section', 'create_custom::field::section',
        'update_custom::field::section', 'delete_custom::field::section', 'delete_any_custom::field::section',
        'view_workflow', 'view_any_workflow', 'create_workflow',
        'update_workflow', 'delete_workflow', 'delete_any_workflow',
        'view_qr::label::format', 'view_any_qr::label::format', 'create_qr::label::format',
        'update_qr::label::format', 'delete_qr::label::format', 'delete_any_qr::label::format',
        'view_secret', 'view_any_secret', 'create_secret',
        'update_secret', 'delete_secret', 'delete_any_secret',
        'view_api::token', 'view_any_api::token', 'create_api::token',
        'update_api::token', 'delete_api::token', 'delete_any_api::token',
    ];

    /**
     * @var list<string>
     */
    public const WASTE_CONFIRM_PERMISSIONS = [
        'confirm_waste::pickup', 'view_any_waste::pickup', 'view_waste::pickup',
        'view_any_waste::container', 'view_waste::container',
    ];

    /**
     * @var list<string>
     */
    public const WASTE_SUPPORT_PERMISSIONS = [
        'view_any_property', 'view_property', 'view_all_property',
        'view_any_company', 'view_company',
        'view_any_contract', 'view_contract',
        'view_any_ticket', 'view_ticket', 'create_ticket',
    ];

    /**
     * @var list<string>
     */
    public const USER_VERBOTEN = [
    ];

    public function provision(Organization $organization): Role
    {
        $previousTenant = Filament::getTenant();
        $previousTeamId = getPermissionsTeamId();

        Filament::setTenant($organization, isQuiet: true);
        setPermissionsTeamId($organization->getKey());

        try {
            $admin = $this->syncRole($organization, 'Admin', Permission::all());

            $userPermissions = Permission::query()
                ->whereNotIn('name', [...self::USER_VERBOTEN, ...self::NUR_ADMIN_PERMISSIONS])
                ->get();

            $this->syncRole($organization, 'User', $userPermissions);
            $this->syncRole($organization, 'FM', $userPermissions);
            $this->syncRole($organization, 'PM', $userPermissions);

            $this->syncRole($organization, 'Melder', Permission::query()
                ->whereIn('name', ['view_any_ticket', 'view_ticket', 'create_ticket', 'view_any_asset', 'view_asset'])
                ->get());

            $this->syncRole($organization, 'Dienstleister', Permission::query()
                ->whereIn('name', [
                    'view_any_ticket', 'view_ticket', 'update_ticket',
                    'view_any_maintenance::order', 'view_maintenance::order', 'update_maintenance::order',
                    ...self::WASTE_CONFIRM_PERMISSIONS,
                ])
                ->get());

            $this->syncRole($organization, 'Abfall', Permission::query()
                ->where(fn ($query) => $query
                    ->where('name', 'like', '%_waste::%')
                    ->orWhereIn('name', self::WASTE_SUPPORT_PERMISSIONS))
                ->get());

            $this->syncRole($organization, 'Verträge', Permission::query()
                ->whereIn('name', [
                    'view_any_contract', 'view_contract', 'create_contract',
                    'update_contract', 'delete_contract', 'delete_any_contract',
                ])
                ->get());
        } finally {
            Filament::setTenant($previousTenant, isQuiet: true);
            setPermissionsTeamId($previousTeamId);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     */
    private function syncRole(Organization $organization, string $name, $permissions): Role
    {
        $role = Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
            'organization_id' => $organization->getKey(),
        ]);

        $role->syncPermissions($permissions);

        return $role;
    }
}
