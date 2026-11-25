<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structure_nodes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('structure_nodes')->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->integer('sort')->default(0);
            $table->smallInteger('level')->nullable();
            $table->string('room_use')->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->boolean('heated')->nullable();
            $table->string('ifc_guid', 64)->nullable();
            $table->timestamps();
            $table->index('ifc_guid');
            $table->index(['property_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('structure_nodes');
    }
};
