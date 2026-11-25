<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('waste_settings', 'rule_sources')) {
            Schema::table('waste_settings', function (Blueprint $table): void {
                $table->json('rule_sources')->nullable()->after('rules');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('waste_settings', 'rule_sources')) {
            Schema::table('waste_settings', function (Blueprint $table): void {
                $table->dropColumn('rule_sources');
            });
        }
    }
};
