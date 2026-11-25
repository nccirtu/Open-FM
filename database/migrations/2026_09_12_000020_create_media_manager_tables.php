<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 64)->nullable();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'parent_id']);
        });

        Schema::create('media_files', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 64)->nullable();
            $table->foreignUuid('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'folder_id']);
        });

        Schema::create('media_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id', 64)->nullable();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('media_taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id')->constrained('media_tags')->cascadeOnDelete();
            $table->string('taggable_type');
            $table->uuid('taggable_id');
            $table->index(['taggable_type', 'taggable_id']);
        });

        Schema::create('media_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
            $table->string('attachable_type');
            $table->uuid('attachable_id');
            $table->string('collection')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_attachments');
        Schema::dropIfExists('media_taggables');
        Schema::dropIfExists('media_tags');
        Schema::dropIfExists('media_files');
        Schema::dropIfExists('media_folders');
    }
};
