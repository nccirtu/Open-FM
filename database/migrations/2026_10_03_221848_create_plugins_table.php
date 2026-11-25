<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plugins')) {
            Schema::create('plugins', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('slug')->unique();
                $table->string('package')->unique();
                $table->string('name');
                $table->string('version')->nullable();
                $table->string('source')->default('composer');
                $table->string('status')->default('installed');
                $table->boolean('is_enabled')->default(true);
                $table->text('license_key')->nullable();
                $table->string('license_status')->nullable();
                $table->timestamp('license_checked_at')->nullable();
                $table->text('last_error')->nullable();
                $table->json('settings')->nullable();
                $table->timestamp('installed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('organization_plugin')) {
            Schema::create('organization_plugin', function (Blueprint $table): void {
                $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('plugin_id')->constrained()->cascadeOnDelete();
                $table->primary(['organization_id', 'plugin_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_plugin');
        Schema::dropIfExists('plugins');
    }
};
