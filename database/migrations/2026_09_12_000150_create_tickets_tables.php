<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_definitions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->string('priority');
            $table->unsignedInteger('response_hours');
            $table->unsignedInteger('resolution_hours');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'priority'], 'sla_org_priority_unique');
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('type')->default('fault');
            $table->string('category');
            $table->string('priority')->default('p3');
            $table->string('status')->default('reported');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('photos')->nullable();
            $table->foreignUuid('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('seen_at')->nullable();
            $table->timestamps();
            $table->foreignUuid('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->dateTime('respond_due_at')->nullable();
            $table->dateTime('resolve_due_at')->nullable();
            $table->dateTime('first_response_at')->nullable();
            $table->dateTime('sla_escalated_at')->nullable();
            $table->dateTime('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();
            $table->string('estimate_status')->nullable();
            $table->decimal('estimate_amount', 12, 2)->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->foreignUuid('structure_node_id')->nullable()->constrained('structure_nodes')->nullOnDelete();
            $table->index('priority');
            $table->index('status');
        });

        Schema::create('ticket_replies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->longText('attachments')->nullable();
            $table->boolean('is_internal_note')->default(false);
            $table->timestamps();
        });

        Schema::create('ticket_automation_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('event');
            $table->longText('conditions')->nullable();
            $table->longText('actions');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_automation_rules');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('sla_definitions');
    }
};
