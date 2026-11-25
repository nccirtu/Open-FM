<?php

use App\Services\OrganizationRoleProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_filter(OrganizationRoleProvisioner::NUR_ADMIN_PERMISSIONS, fn (string $name): bool => str_ends_with($name, '_api::token')))
            ->pluck('id');

        $roleIds = DB::table('roles')->where('name', '!=', 'Admin')->pluck('id');

        if ($permissionIds->isEmpty() || $roleIds->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->whereIn('role_id', $roleIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}
};
