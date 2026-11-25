<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('waste_pickups')) {
            DB::table('waste_pickups')
                ->where('status', 'assumed')
                ->update([
                    'status' => 'confirmed',
                    'confirmed_via' => 'schedule',
                    'confirmed_at' => DB::raw(Schema::hasColumn('waste_pickups', 'assumed_at') ? 'COALESCE(assumed_at, updated_at)' : 'updated_at'),
                ]);

            foreach (['assumed_at', 'escalation_level'] as $column) {
                if (Schema::hasColumn('waste_pickups', $column)) {
                    Schema::table('waste_pickups', fn (Blueprint $table) => $table->dropColumn($column));
                }
            }
        }

        if (Schema::hasTable('waste_collections')) {
            DB::table('waste_collections')->where('mass_source', 'assumed')->update(['mass_source' => 'estimated']);
        }

        if (Schema::hasColumn('waste_daily_values', 'assumed_mass_kg')) {
            Schema::table('waste_daily_values', fn (Blueprint $table) => $table->dropColumn('assumed_mass_kg'));
        }

        foreach (['reminder_days', 'ticket_days'] as $column) {
            if (Schema::hasColumn('waste_settings', $column)) {
                Schema::table('waste_settings', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('waste_pickups', 'assumed_at')) {
            Schema::table('waste_pickups', function (Blueprint $table): void {
                $table->timestamp('assumed_at')->nullable();
                $table->unsignedTinyInteger('escalation_level')->default(0);
            });
        }

        if (! Schema::hasColumn('waste_daily_values', 'assumed_mass_kg')) {
            Schema::table('waste_daily_values', fn (Blueprint $table) => $table->decimal('assumed_mass_kg', 14, 2)->default(0));
        }

        if (! Schema::hasColumn('waste_settings', 'reminder_days')) {
            Schema::table('waste_settings', function (Blueprint $table): void {
                $table->unsignedSmallInteger('reminder_days')->default(3);
                $table->unsignedSmallInteger('ticket_days')->default(5);
            });
        }
    }
};
