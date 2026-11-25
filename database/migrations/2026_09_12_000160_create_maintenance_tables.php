<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plan_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('asset_type_id')->constrained('asset_types')->cascadeOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('name');
            $table->string('type')->default('maintenance');
            $table->string('legal_basis')->nullable();
            $table->unsignedInteger('interval_value');
            $table->string('interval_unit')->default('months');
            $table->unsignedInteger('lead_time_days')->default(14);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->longText('tasks')->nullable();
            $table->unique(['organization_id', 'asset_type_id', 'name'], 'mpt_org_type_name_unique');
        });

        Schema::create('maintenance_plans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignUuid('maintenance_plan_template_id')->nullable()->constrained('maintenance_plan_templates')->nullOnDelete();
            $table->string('name');
            $table->string('type')->default('maintenance');
            $table->string('legal_basis')->nullable();
            $table->unsignedInteger('interval_value');
            $table->string('interval_unit')->default('months');
            $table->date('next_due_at');
            $table->unsignedInteger('lead_time_days')->default(14);
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('inspection_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignUuid('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->longText('tasks')->nullable();
            $table->index('next_due_at');
            $table->unique(['maintenance_plan_template_id', 'asset_id'], 'mp_template_asset_unique');
        });

        Schema::create('maintenance_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('maintenance_plan_id')->constrained('maintenance_plans')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('due_at');
            $table->string('status')->default('open');
            $table->dateTime('completed_at')->nullable();
            $table->foreignUuid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->longText('protocol_files')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->longText('tasks')->nullable();
            $table->longText('completed_tasks')->nullable();
            $table->index('due_at');
            $table->unique(['maintenance_plan_id', 'due_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_orders');
        Schema::dropIfExists('maintenance_plans');
        Schema::dropIfExists('maintenance_plan_templates');
    }
};
