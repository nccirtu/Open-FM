<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_nodes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('classification_nodes')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
        });

        Schema::create('asset_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->unique('name');
        });

        Schema::create('asset_type_classifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('asset_type_id')->constrained('asset_types')->cascadeOnDelete();
            $table->foreignUuid('classification_node_id')->constrained('classification_nodes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'asset_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_type_classifications');
        Schema::dropIfExists('asset_types');
        Schema::dropIfExists('classification_nodes');
    }
};
