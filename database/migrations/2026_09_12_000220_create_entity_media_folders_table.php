<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_media_folders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('folder_id')->constrained('media_folders')->cascadeOnDelete();
            $table->string('folderable_type');
            $table->uuid('folderable_id');
            $table->timestamps();
            $table->index(['folderable_type', 'folderable_id']);
            $table->unique(['folderable_type', 'folderable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_media_folders');
    }
};
