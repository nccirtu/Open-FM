<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedInteger('asset_number')->nullable();
            $table->string('name');
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('structure_node_id')->nullable()->constrained('structure_nodes')->nullOnDelete();
            $table->foreignUuid('asset_type_id')->nullable()->constrained('asset_types')->nullOnDelete();
            $table->string('manufacturer')->nullable();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('inspection_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->date('last_maintenance_at')->nullable();
            $table->date('next_maintenance_at')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->foreignUuid('parent_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('condition')->nullable();
            $table->string('service_status')->nullable();
            $table->date('warranty_until')->nullable();
            $table->date('installed_at')->nullable();
            $table->date('decommissioned_at')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->string('ifc_guid', 64)->nullable();
            $table->index('ifc_guid');
            $table->unique(['organization_id', 'asset_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
