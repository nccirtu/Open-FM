<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PERMISSIONS = [
        'view_announcement', 'view_any_announcement', 'create_announcement',
        'update_announcement', 'delete_announcement', 'delete_any_announcement',
    ];

    private const WIDGET = 'App\\Filament\\Portal\\Widgets\\AnnouncementBoardWidget';

    public function up(): void
    {
        Schema::dropIfExists('announcements');

        $this->removePermissions();
        $this->removeWidgetFromLayouts();
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements')) {
            return;
        }

        Schema::create('announcements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_published')->default(true);
            $table->date('published_until')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    private function removePermissions(): void
    {
        $tables = config('permission.table_names');

        if (! is_array($tables) || ! Schema::hasTable($tables['permissions'])) {
            return;
        }

        $ids = DB::table($tables['permissions'])->whereIn('name', self::PERMISSIONS)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table($tables['role_has_permissions'])->whereIn('permission_id', $ids)->delete();
        DB::table($tables['model_has_permissions'])->whereIn('permission_id', $ids)->delete();
        DB::table($tables['permissions'])->whereIn('id', $ids)->delete();
    }

    private function removeWidgetFromLayouts(): void
    {
        foreach (['widget_grid_layouts', 'widget_grid_templates'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->select(['id', 'items'])->each(function (object $row) use ($table): void {
                $stored = json_decode((string) $row->items, true);

                if (! is_array($stored)) {
                    return;
                }

                $isWrapped = isset($stored['items']) && is_array($stored['items']);
                $items = $isWrapped ? $stored['items'] : $stored;

                $remaining = array_values(array_filter(
                    $items,
                    fn (mixed $item): bool => ! is_array($item) || ($item['widget'] ?? null) !== self::WIDGET,
                ));

                if (count($remaining) === count($items)) {
                    return;
                }

                if ($isWrapped) {
                    $stored['items'] = $remaining;
                } else {
                    $stored = $remaining;
                }

                DB::table($table)->where('id', $row->id)->update(['items' => json_encode($stored)]);
            });
        }
    }
};
