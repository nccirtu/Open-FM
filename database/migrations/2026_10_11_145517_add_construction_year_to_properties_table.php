<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('properties', 'construction_year')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->unsignedSmallInteger('construction_year')->nullable()->after('country');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'construction_year')) {
            Schema::table('properties', function (Blueprint $table): void {
                $table->dropColumn('construction_year');
            });
        }
    }
};
