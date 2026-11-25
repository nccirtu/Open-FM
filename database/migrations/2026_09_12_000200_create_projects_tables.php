<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30)->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->longText('phases')->nullable();
            $table->longText('milestones')->nullable();
            $table->longText('tasks')->nullable();
            $table->longText('budget_positions')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index('organization_id');
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->foreignUuid('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignUuid('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 30)->nullable();
            $table->string('status', 20)->default('open');
            $table->string('health', 10)->default('green');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index('organization_id');
            $table->index('status');
        });

        Schema::create('project_phases', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->string('status', 20)->default('planned');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('milestones', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('project_phases')->nullOnDelete();
            $table->string('name');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->nullable();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('phase_id')->nullable()->constrained('project_phases')->nullOnDelete();
            $table->foreignUuid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->foreignUuid('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('blocked_by_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('todo');
            $table->string('priority', 10)->default('normal');
            $table->string('trade', 100)->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('position', 20, 10)->nullable();
            $table->longText('checklist')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->index('organization_id');
            $table->index(['project_id', 'status', 'position']);
        });

        Schema::create('budget_positions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('cost_group', 50)->nullable();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('classification_nodes')->nullOnDelete();
            $table->decimal('budget', 14, 2)->default(0.00);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cost_entries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('budget_position_id')->nullable()->constrained('budget_positions')->nullOnDelete();
            $table->foreignUuid('cost_center_id')->nullable()->constrained('classification_nodes')->nullOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('kind', 20)->default('actual');
            $table->string('receipt_type', 20)->default('expense');
            $table->decimal('amount', 14, 2);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('net_amount', 14, 2)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('booked_on');
            $table->date('due_on')->nullable();
            $table->string('vendor')->nullable();
            $table->string('description')->nullable();
            $table->string('receipt')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
            $table->string('costable_type')->nullable();
            $table->uuid('costable_id')->nullable();
            $table->index(['costable_type', 'costable_id']);
            $table->index(['project_id', 'kind']);
        });

        Schema::create('change_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignUuid('budget_position_id')->nullable()->constrained('budget_positions')->nullOnDelete();
            $table->string('title');
            $table->text('reason')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('pending');
            $table->date('requested_on')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_orders');
        Schema::dropIfExists('cost_entries');
        Schema::dropIfExists('budget_positions');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('project_phases');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_templates');
    }
};
