<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('properties', 'usage_type')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('usage_type', 20)->default('commercial')->after('manager_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('properties', 'usage_type')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropColumn('usage_type');
        });
    }
};
