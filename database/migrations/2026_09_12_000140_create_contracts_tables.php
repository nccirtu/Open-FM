<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->string('name');
            $table->string('contract_number')->nullable();
            $table->string('partner_name');
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('type')->default('service');
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('notice_period_days')->nullable();
            $table->unsignedInteger('reminder_lead_days')->default(60);
            $table->dateTime('reminded_at')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('billing_cycle')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('end_date');
            $table->index('status');
        });

        Schema::create('contract_property', function (Blueprint $table): void {
            $table->foreignUuid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->primary(['contract_id', 'property_id']);
        });

        Schema::create('asset_contract', function (Blueprint $table): void {
            $table->foreignUuid('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->primary(['contract_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_contract');
        Schema::dropIfExists('contract_property');
        Schema::dropIfExists('contracts');
    }
};
