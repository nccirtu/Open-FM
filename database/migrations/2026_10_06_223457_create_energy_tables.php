<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('meters')) {
            Schema::create('meters', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('parent_id')->nullable()->constrained('meters')->nullOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->foreignUuid('structure_node_id')->nullable()->constrained('structure_nodes')->nullOnDelete();
                $table->string('name');
                $table->string('number')->nullable();
                $table->string('medium', 30);
                $table->string('kind', 20)->default('sub');
                $table->string('unit', 20)->default('kWh');
                $table->decimal('factor', 14, 6)->default(1);
                $table->json('formula')->nullable();
                $table->string('market_location_id')->nullable();
                $table->date('calibration_valid_until')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'medium']);
                $table->index('number');
            });
        }

        if (! Schema::hasTable('meter_structure_node')) {
            Schema::create('meter_structure_node', function (Blueprint $table): void {
                $table->foreignUuid('meter_id')->constrained('meters')->cascadeOnDelete();
                $table->foreignUuid('structure_node_id')->constrained('structure_nodes')->cascadeOnDelete();
                $table->decimal('share', 5, 2)->default(100);
                $table->primary(['meter_id', 'structure_node_id']);
            });
        }

        if (! Schema::hasTable('asset_meter')) {
            Schema::create('asset_meter', function (Blueprint $table): void {
                $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignUuid('meter_id')->constrained('meters')->cascadeOnDelete();
                $table->decimal('share', 5, 2)->default(100);
                $table->primary(['asset_id', 'meter_id']);
            });
        }

        if (! Schema::hasTable('meter_readings')) {
            Schema::create('meter_readings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('meter_id')->constrained('meters')->cascadeOnDelete();
                $table->dateTime('read_at');
                $table->decimal('value', 18, 4);
                $table->string('source', 20);
                $table->string('status', 20)->default('plausible');
                $table->string('flag', 30)->nullable();
                $table->string('photo')->nullable();
                $table->text('note')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['meter_id', 'read_at']);
            });
        }

        if (! Schema::hasTable('meter_daily_values')) {
            Schema::create('meter_daily_values', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('meter_id')->constrained('meters')->cascadeOnDelete();
                $table->date('date');
                $table->decimal('consumption', 18, 4);
                $table->json('intervals')->nullable();
                $table->timestamps();

                $table->unique(['meter_id', 'date']);
                $table->index('date');
            });
        }

        if (! Schema::hasTable('energy_goals')) {
            Schema::create('energy_goals', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->string('name');
                $table->nullableUuidMorphs('goalable');
                $table->string('medium', 30);
                $table->string('type', 30);
                $table->string('period', 10)->default('year');
                $table->unsignedSmallInteger('year');
                $table->decimal('target_value', 16, 2);
                $table->unsignedSmallInteger('baseline_year')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_goals');
        Schema::dropIfExists('meter_daily_values');
        Schema::dropIfExists('meter_readings');
        Schema::dropIfExists('asset_meter');
        Schema::dropIfExists('meter_structure_node');
        Schema::dropIfExists('meters');
    }
};
