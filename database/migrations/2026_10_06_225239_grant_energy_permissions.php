<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUBJECTS = ['meter', 'meter::reading', 'energy::goal'];

    private const AFFIXES = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'];

    private const ROLES = ['Admin', 'User', 'FM', 'PM'];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! is_array($tables) || ! Schema::hasTable($tables['permissions']) || ! DB::table($tables['permissions'])->exists()) {
            return;
        }

        $pivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';
        $roleIds = DB::table($tables['roles'])->whereIn('name', self::ROLES)->pluck('id');

        foreach ($this->names() as $name) {
            $permissionId = DB::table($tables['permissions'])->where('name', $name)->where('guard_name', 'web')->value('id')
                ?? DB::table($tables['permissions'])->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($roleIds as $roleId) {
                DB::table($tables['role_has_permissions'])->insertOrIgnore([
                    $permissionKey => $permissionId,
                    $pivotKey => $roleId,
                ]);
            }
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
        $ids = DB::table($tables['permissions'])->whereIn('name', $this->names())->pluck('id');

        DB::table($tables['role_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['model_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['permissions'])->whereIn('id', $ids)->delete();

        app('cache')->forget(config('permission.cache.key'));
    }

    /**
     * @return array<int, string>
     */
    private function names(): array
    {
        $names = [];

        foreach (self::SUBJECTS as $subject) {
            foreach (self::AFFIXES as $affix) {
                $names[] = "{$affix}_{$subject}";
            }
        }

        return $names;
    }
};
