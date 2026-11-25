<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('property_number');
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->decimal('gross_area', 10, 2)->nullable();
            $table->decimal('net_area', 10, 2)->nullable();
            $table->decimal('cleanable_area', 10, 2)->nullable();
            $table->decimal('heated_volume', 12, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('energy_rating', 5)->nullable();
            $table->date('energy_cert_valid_until')->nullable();
            $table->unique(['organization_id', 'property_number']);
        });

        Schema::create('property_user', function (Blueprint $table): void {
            $table->foreignUuid('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['property_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_user');
        Schema::dropIfExists('properties');
    }
};
