<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->relabel(from: 24, to: 12);
    }

    public function down(): void
    {
        $this->relabel(from: 12, to: 24);
    }

    private function relabel(int $from, int $to): void
    {
        foreach (['widget_grid_layouts', 'widget_grid_templates'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->orderBy('id')->select(['id', 'items'])->each(function (object $row) use ($table, $from, $to): void {
                $stored = json_decode((string) $row->items, true);

                if (! is_array($stored) || ! isset($stored['items']) || (int) ($stored['columns'] ?? 0) !== $from) {
                    return;
                }

                $stored['columns'] = $to;

                DB::table($table)->where('id', $row->id)->update(['items' => json_encode($stored)]);
            });
        }
    }
};
