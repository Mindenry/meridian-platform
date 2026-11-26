<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->string('path')->index();
            $table->timestamps();
            $table->unique(['site_id', 'path']);
        });

        Schema::create('media_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('media_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 120);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('alt')->nullable();
            $table->json('caption')->nullable();
            $table->string('credit')->nullable();
            $table->string('copyright')->nullable();
            $table->string('license')->nullable();
            $table->string('source_url', 1000)->nullable();
            $table->decimal('focal_x', 5, 4)->default(0.5);
            $table->decimal('focal_y', 5, 4)->default(0.5);
            $table->json('variants')->nullable();
            $table->string('checksum', 64)->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'checksum']);
            $table->index(['site_id', 'folder_id']);
        });

        Schema::create('media_collection_items', function (Blueprint $table) {
            $table->foreignId('media_collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->primary(['media_collection_id', 'media_asset_id']);
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')->constrained()->cascadeOnDelete();
            $table->string('usable_type');
            $table->unsignedBigInteger('usable_id');
            $table->string('field')->default('media');
            $table->timestamps();
            $table->unique(['media_asset_id', 'usable_type', 'usable_id', 'field'], 'media_usage_unique');
            $table->index(['usable_type', 'usable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_collection_items');
        Schema::dropIfExists('media_assets');
        Schema::dropIfExists('media_collections');
        Schema::dropIfExists('media_folders');
    }
};
