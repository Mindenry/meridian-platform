<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('template')->default('default');
            $table->string('status')->default('draft')->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_home')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'status']);
            $table->index(['site_id', 'parent_id']);
        });

        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->unsignedSmallInteger('blocks_version')->default(1);
            $table->timestamps();
            $table->unique(['page_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
            $table->index(['site_id', 'locale']);
        });

        Schema::create('page_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('block_uid', 40);
            $table->string('type', 60);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('props');
            $table->boolean('is_visible_desktop')->default(true);
            $table->boolean('is_visible_tablet')->default(true);
            $table->boolean('is_visible_mobile')->default(true);
            $table->json('responsive')->nullable();
            $table->timestamps();
            $table->unique(['page_id', 'locale', 'block_uid']);
            $table->index(['page_id', 'locale', 'sort_order']);
        });

        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['page_id', 'locale', 'version']);
        });

        Schema::create('page_preview_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('token', 80)->unique();
            $table->string('locale', 10);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('scheduled_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->nullable();
            $table->timestamp('publish_at');
            $table->string('status')->default('pending')->index();
            $table->timestamp('published_at')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'publish_at']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10)->nullable();
            $table->string('from_path');
            $table->string('to_path');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->unsignedBigInteger('hits')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'locale', 'from_path']);
        });

        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');
            $table->string('locale', 10);
            $table->string('title')->nullable();
            $table->string('description', 500)->nullable();
            $table->string('canonical', 1000)->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('robots')->default('index,follow');
            $table->string('schema_type')->nullable();
            $table->json('schema')->nullable();
            $table->json('breadcrumbs')->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id', 'locale'], 'seo_unique');
            $table->index(['site_id', 'locale']);
        });

        Schema::create('seo_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->json('summary')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        Schema::create('seo_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seo_audit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('seoable_type')->nullable();
            $table->unsignedBigInteger('seoable_id')->nullable();
            $table->string('locale', 10);
            $table->string('code', 80);
            $table->string('severity', 20)->default('warning');
            $table->string('url', 1000)->nullable();
            $table->string('title')->nullable();
            $table->text('explanation');
            $table->text('recommendation');
            $table->string('status')->default('open');
            $table->timestamp('last_checked_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->unique(['seoable_type', 'seoable_id', 'locale', 'code'], 'seo_issue_unique');
            $table->index(['site_id', 'status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_issues');
        Schema::dropIfExists('seo_audits');
        Schema::dropIfExists('seo_metadata');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('scheduled_publications');
        Schema::dropIfExists('page_preview_tokens');
        Schema::dropIfExists('page_revisions');
        Schema::dropIfExists('page_blocks');
        Schema::dropIfExists('page_translations');
        Schema::dropIfExists('pages');
    }
};
