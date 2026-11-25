<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUBJECTS = ['property', 'project'];

    private const ABILITIES = ['view', 'create', 'update', 'delete'];

    public function up(): void
    {
        $tables = config('permission.table_names');

        if (! is_array($tables) || ! Schema::hasTable($tables['permissions']) || ! DB::table($tables['permissions'])->exists()) {
            return;
        }

        $pivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        foreach (self::SUBJECTS as $subject) {
            foreach (self::ABILITIES as $ability) {
                $base = DB::table($tables['permissions'])->where('name', "{$ability}_{$subject}")->where('guard_name', 'web')->first();

                if ($base === null) {
                    continue;
                }

                $scopedId = $this->permissionId($tables['permissions'], "{$ability}_all_{$subject}");

                $roleIds = DB::table($tables['role_has_permissions'])->where($permissionKey, $base->id)->pluck($pivotKey);

                foreach ($roleIds as $roleId) {
                    DB::table($tables['role_has_permissions'])->insertOrIgnore([
                        $permissionKey => $scopedId,
                        $pivotKey => $roleId,
                    ]);
                }
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

        $names = [];

        foreach (self::SUBJECTS as $subject) {
            foreach (self::ABILITIES as $ability) {
                $names[] = "{$ability}_all_{$subject}";
            }
        }

        $ids = DB::table($tables['permissions'])->whereIn('name', $names)->pluck('id');
        $permissionKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

        DB::table($tables['role_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['model_has_permissions'])->whereIn($permissionKey, $ids)->delete();
        DB::table($tables['permissions'])->whereIn('id', $ids)->delete();

        app('cache')->forget(config('permission.cache.key'));
    }

    private function permissionId(string $table, string $name): int
    {
        $existing = DB::table($table)->where('name', $name)->where('guard_name', 'web')->value('id');

        if ($existing !== null) {
            return (int) $existing;
        }

        return (int) DB::table($table)->insertGetId([
            'name' => $name,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
