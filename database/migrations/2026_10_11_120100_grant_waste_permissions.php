<?php

use App\Services\OrganizationRoleProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUBJECTS = [
        'waste::fraction', 'waste::container', 'waste::schedule', 'waste::pickup', 'waste::collection',
        'waste::fraction::group', 'waste::container::type', 'waste::treatment', 'waste::treatment::category',
        'waste::goal', 'waste::evidence', 'waste::emission::factor',
    ];

    private const AFFIXES = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'];

    private const EXTRA = ['confirm_waste::pickup'];

    private const ROLES = ['Admin', 'User', 'FM', 'PM'];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! is_array($tables) || ! Schema::hasTable($tables['permissions']) || ! DB::table($tables['permissions'])->exists()) {
            return;
        }

        $ids = collect($this->names())->mapWithKeys(fn (string $name): array => [$name => $this->permissionId($tables['permissions'], $name)]);

        $this->grant($tables, DB::table($tables['roles'])->whereIn('name', self::ROLES)->pluck('id'), $ids->values());

        $this->grant(
            $tables,
            DB::table($tables['roles'])->where('name', 'Dienstleister')->pluck('id'),
            $ids->only(OrganizationRoleProvisioner::WASTE_CONFIRM_PERMISSIONS)->values(),
        );

        $teamKey = config('permission.column_names.team_foreign_key') ?? 'organization_id';
        $supportIds = DB::table($tables['permissions'])->whereIn('name', OrganizationRoleProvisioner::WASTE_SUPPORT_PERMISSIONS)->pluck('id');

        foreach (DB::table('organizations')->pluck('id') as $organizationId) {
            $roleId = DB::table($tables['roles'])->where('name', 'Abfall')->where($teamKey, $organizationId)->value('id')
                ?? DB::table($tables['roles'])->insertGetId([
                    'name' => 'Abfall',
                    'guard_name' => 'web',
                    $teamKey => $organizationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->grant($tables, collect([$roleId]), $ids->values()->merge($supportIds));
        }

        app('cache')->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        if (! is_array($tables) || ! Schema::hasTable($tables['permissions'])) {
            return;
        }

        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';
        $pivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $ids = DB::table($tables['permissions'])->whereIn('name', $this->names())->pluck('id');
        $roleIds = DB::table($tables['roles'])->where('name', 'Abfall')->pluck('id');

        DB::table($tables['role_has_permissions'])->whereIn($pivotKey, $roleIds)->delete();
        DB::table($tables['model_has_roles'])->whereIn($pivotKey, $roleIds)->delete();
        DB::table($tables['roles'])->whereIn('id', $roleIds)->delete();
        DB::table($tables['role_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['model_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['permissions'])->whereIn('id', $ids)->delete();

        app('cache')->forget(config('permission.cache.key'));
    }

    /**
     * @param  array<string, string>  $tables
     * @param  Collection<int, int|string>  $roleIds
     * @param  Collection<int, int|string>  $permissionIds
     */
    private function grant(array $tables, $roleIds, $permissionIds): void
    {
        $pivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table($tables['role_has_permissions'])->insertOrIgnore([
                    $permissionKey => $permissionId,
                    $pivotKey => $roleId,
                ]);
            }
        }
    }

    private function permissionId(string $table, string $name): int|string
    {
        return DB::table($table)->where('name', $name)->where('guard_name', 'web')->value('id')
            ?? DB::table($table)->insertGetId([
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function names(): array
    {
        $names = self::EXTRA;

        foreach (self::SUBJECTS as $subject) {
            foreach (self::AFFIXES as $affix) {
                $names[] = "{$affix}_{$subject}";
            }
        }

        return $names;
    }
};
