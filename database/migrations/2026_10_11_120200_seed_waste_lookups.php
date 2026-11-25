<?php

use App\Services\Waste\WasteLookupDefaults;
use Database\Seeders\WasteLookupSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waste_treatment_categories') || ! Schema::hasTable('organizations')) {
            return;
        }

        (new WasteLookupSeeder)->run(app(WasteLookupDefaults::class));
    }

    public function down(): void {}
};
