<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('properties', 'manager_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->foreignUuid('manager_id')->nullable()->after('organization_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('properties', 'manager_id')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manager_id');
        });
    }
};
