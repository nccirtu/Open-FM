<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('waste_settings')) {
            Schema::create('waste_settings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->unique()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('country', 10)->nullable();
                $table->unsignedTinyInteger('cutoff_days')->default(1);
                $table->string('cutoff_time', 5)->default('09:00');
                $table->unsignedTinyInteger('reminder_days')->default(3);
                $table->unsignedTinyInteger('ticket_days')->default(5);
                $table->unsignedTinyInteger('horizon_weeks')->default(8);
                $table->unsignedTinyInteger('default_fill_pct')->default(75);
                $table->string('staging_reminder_time', 5)->default('18:00');
                $table->unsignedTinyInteger('request_reminder_days')->default(2);
                $table->unsignedTinyInteger('sensor_request_pct')->default(80);
                $table->json('rules')->nullable();
                $table->json('evidence_types')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('waste_treatment_categories')) {
            Schema::create('waste_treatment_categories', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('key', 40)->nullable();
                $table->string('name');
                $table->boolean('is_recovery')->default(false);
                $table->boolean('counts_as_recycling')->default(false);
                $table->string('esrs_category', 30)->nullable();
                $table->string('color', 20)->nullable();
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'key']);
            });
        }

        if (! Schema::hasTable('waste_treatments')) {
            Schema::create('waste_treatments', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignUuid('waste_treatment_category_id')->nullable()->constrained('waste_treatment_categories')->nullOnDelete();
                $table->string('code', 20)->nullable();
                $table->string('name');
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'code']);
            });
        }

        if (! Schema::hasTable('waste_fraction_groups')) {
            Schema::create('waste_fraction_groups', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('key', 40)->nullable();
                $table->string('name');
                $table->boolean('counts_as_separated')->default(true);
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'key']);
            });
        }

        if (! Schema::hasTable('waste_container_types')) {
            Schema::create('waste_container_types', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('key', 40)->nullable();
                $table->string('name');
                $table->unsignedInteger('default_volume_l')->nullable();
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'key']);
            });
        }

        if (! Schema::hasTable('waste_fractions')) {
            Schema::create('waste_fractions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->string('name');
                $table->string('code', 20)->nullable();
                $table->boolean('is_hazardous')->default(false);
                $table->foreignUuid('waste_fraction_group_id')->nullable()->constrained('waste_fraction_groups')->nullOnDelete();
                $table->string('color', 20)->nullable();
                $table->decimal('density_kg_m3', 8, 2)->nullable();
                $table->foreignUuid('waste_treatment_id')->nullable()->constrained('waste_treatments')->nullOnDelete();
                $table->string('template_key', 20)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'code']);
            });
        }

        if (! Schema::hasTable('waste_containers')) {
            Schema::create('waste_containers', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('structure_node_id')->nullable()->constrained('structure_nodes')->nullOnDelete();
                $table->foreignUuid('waste_fraction_id')->constrained('waste_fractions')->restrictOnDelete();
                $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignUuid('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
                $table->foreignUuid('waste_container_type_id')->nullable()->constrained('waste_container_types')->nullOnDelete();
                $table->string('number', 50);
                $table->unsignedInteger('volume_l');
                $table->unsignedTinyInteger('usual_fill_pct')->nullable();
                $table->decimal('price_per_pickup', 10, 2)->nullable();
                $table->decimal('price_per_tonne', 10, 2)->nullable();
                $table->decimal('monthly_rent', 10, 2)->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->string('ifc_guid')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'is_active']);
                $table->index(['organization_id', 'number']);
            });
        }

        if (! Schema::hasTable('waste_schedules')) {
            Schema::create('waste_schedules', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('structure_node_id')->nullable()->constrained('structure_nodes')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('source', 20)->default('rule');
                $table->unsignedTinyInteger('interval_value')->default(1);
                $table->string('interval_unit', 10)->default('week');
                $table->unsignedTinyInteger('weekday')->nullable();
                $table->tinyInteger('week_of_month')->nullable();
                $table->time('window_from')->nullable();
                $table->time('window_to')->nullable();
                $table->boolean('needs_staging')->default(false);
                $table->json('confirmation_channels')->nullable();
                $table->json('ics_dates')->nullable();
                $table->string('ics_filter')->nullable();
                $table->date('starts_on');
                $table->date('ends_on')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('waste_container_waste_schedule')) {
            Schema::create('waste_container_waste_schedule', function (Blueprint $table): void {
                $table->foreignUuid('waste_container_id')->constrained('waste_containers')->cascadeOnDelete();
                $table->foreignUuid('waste_schedule_id')->constrained('waste_schedules')->cascadeOnDelete();
                $table->primary(['waste_container_id', 'waste_schedule_id']);
            });
        }

        if (! Schema::hasTable('waste_pickups')) {
            Schema::create('waste_pickups', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('waste_schedule_id')->nullable()->constrained('waste_schedules')->nullOnDelete();
                $table->foreignUuid('waste_container_id')->constrained('waste_containers')->cascadeOnDelete();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->date('due_on')->nullable();
                $table->string('kind', 20)->default('regular');
                $table->boolean('free_of_charge')->default(false);
                $table->string('status', 20)->default('planned');
                $table->dateTime('requested_at')->nullable();
                $table->dateTime('confirmed_at')->nullable();
                $table->string('confirmed_via', 20)->nullable();
                $table->foreignUuid('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('assumed_at')->nullable();
                $table->dateTime('reminded_at')->nullable();
                $table->dateTime('staging_reminded_at')->nullable();
                $table->string('missed_reason', 30)->nullable();
                $table->text('missed_note')->nullable();
                $table->foreignUuid('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
                $table->unsignedTinyInteger('escalation_level')->default(0);
                $table->timestamps();

                $table->unique(['waste_schedule_id', 'waste_container_id', 'due_on']);
                $table->index(['status', 'due_on']);
                $table->index(['property_id', 'due_on']);
            });
        }

        if (! Schema::hasTable('waste_collections')) {
            Schema::create('waste_collections', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('waste_pickup_id')->nullable()->constrained('waste_pickups')->nullOnDelete();
                $table->foreignUuid('waste_container_id')->nullable()->constrained('waste_containers')->nullOnDelete();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('waste_fraction_id')->constrained('waste_fractions')->restrictOnDelete();
                $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignUuid('project_id')->nullable()->constrained('projects')->nullOnDelete();
                $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
                $table->dateTime('collected_at');
                $table->decimal('mass_kg', 12, 2);
                $table->string('mass_source', 20);
                $table->unsignedTinyInteger('fill_level_pct')->nullable();
                $table->unsignedInteger('volume_l')->nullable();
                $table->foreignUuid('waste_treatment_id')->nullable()->constrained('waste_treatments')->nullOnDelete();
                $table->string('source', 20);
                $table->string('receipt')->nullable();
                $table->string('document_number', 100)->nullable();
                $table->string('certificate_number', 100)->nullable();
                $table->decimal('volume_m3', 10, 2)->nullable();
                $table->boolean('batteries_removed')->nullable();
                $table->text('note')->nullable();
                $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['property_id', 'collected_at']);
                $table->index('project_id');
                $table->index(['waste_fraction_id', 'collected_at']);
            });
        }

        if (! Schema::hasTable('waste_daily_values')) {
            Schema::create('waste_daily_values', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('waste_fraction_id')->constrained('waste_fractions')->cascadeOnDelete();
                $table->date('date');
                $table->foreignUuid('waste_treatment_category_id')->nullable()->constrained('waste_treatment_categories')->nullOnDelete();
                $table->decimal('mass_kg', 14, 2);
                $table->decimal('assumed_mass_kg', 14, 2)->default(0);
                $table->unsignedInteger('collections')->default(0);
                $table->timestamps();

                $table->index(['property_id', 'waste_fraction_id', 'date'], 'waste_daily_values_lookup');
                $table->index('date');
            });
        }

        if (! Schema::hasTable('waste_goals')) {
            Schema::create('waste_goals', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->nullable()->constrained('properties')->cascadeOnDelete();
                $table->string('name');
                $table->string('type', 30);
                $table->unsignedSmallInteger('year');
                $table->decimal('target_value', 12, 2);
                $table->unsignedSmallInteger('baseline_year')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('waste_evidence')) {
            Schema::create('waste_evidence', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
                $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignUuid('waste_fraction_id')->nullable()->constrained('waste_fractions')->nullOnDelete();
                $table->unsignedSmallInteger('year');
                $table->string('type', 40);
                $table->string('status', 20)->default('open');
                $table->date('valid_until')->nullable();
                $table->json('attachments')->nullable();
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'year']);
            });
        }

        if (! Schema::hasTable('waste_emission_factors')) {
            Schema::create('waste_emission_factors', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('waste_fraction_id')->nullable()->constrained('waste_fractions')->cascadeOnDelete();
                $table->foreignUuid('waste_treatment_category_id')->nullable()->constrained('waste_treatment_categories')->cascadeOnDelete();
                $table->decimal('kg_co2e_per_t', 10, 2);
                $table->string('source')->nullable();
                $table->unsignedSmallInteger('valid_year')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('waste_fill_readings')) {
            Schema::create('waste_fill_readings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
                $table->foreignUuid('waste_container_id')->constrained('waste_containers')->cascadeOnDelete();
                $table->dateTime('measured_at');
                $table->unsignedTinyInteger('fill_level_pct');
                $table->decimal('temperature_c', 5, 1)->nullable();
                $table->string('sensor_id', 100)->nullable();
                $table->boolean('pickup_detected')->default(false);
                $table->timestamps();

                $table->index(['waste_container_id', 'measured_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_fill_readings');
        Schema::dropIfExists('waste_emission_factors');
        Schema::dropIfExists('waste_evidence');
        Schema::dropIfExists('waste_goals');
        Schema::dropIfExists('waste_daily_values');
        Schema::dropIfExists('waste_collections');
        Schema::dropIfExists('waste_pickups');
        Schema::dropIfExists('waste_container_waste_schedule');
        Schema::dropIfExists('waste_schedules');
        Schema::dropIfExists('waste_containers');
        Schema::dropIfExists('waste_fractions');
        Schema::dropIfExists('waste_container_types');
        Schema::dropIfExists('waste_fraction_groups');
        Schema::dropIfExists('waste_treatments');
        Schema::dropIfExists('waste_treatment_categories');
        Schema::dropIfExists('waste_settings');
    }
};
