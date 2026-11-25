<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('role', 20)->default('supplier');
            $table->timestamps();
            $table->foreignUuid('organization_id')->nullable()->constrained('organizations')->cascadeOnUpdate();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('website')->nullable();
            $table->string('vat_id', 30)->nullable();
            $table->string('tax_number', 30)->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('bic', 20)->nullable();
            $table->text('notes')->nullable();
            $table->unique(['organization_id', 'name'], 'wartungspartners_organization_id_name_unique');
        });

        Schema::create('company_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type', 20)->default('billing');
            $table->string('label')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('company_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_contacts');
        Schema::dropIfExists('company_addresses');
        Schema::dropIfExists('companies');
    }
};
