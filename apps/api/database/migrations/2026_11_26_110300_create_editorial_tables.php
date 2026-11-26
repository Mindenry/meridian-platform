<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('icon', 60)->default('circuit');
            $table->string('status')->default('published')->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'key']);
        });

        Schema::create('solution_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->string('tagline')->nullable();
            $table->text('overview');
            $table->json('problems')->nullable();
            $table->json('approach')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('benefits')->nullable();
            $table->timestamps();
            $table->unique(['solution_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('icon', 60)->default('factory');
            $table->string('status')->default('published')->index();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'key']);
        });

        Schema::create('industry_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->string('tagline')->nullable();
            $table->text('overview');
            $table->json('challenges')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
            $table->unique(['industry_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
        });

        Schema::create('industry_solution', function (Blueprint $table) {
            $table->foreignId('industry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->primary(['industry_id', 'solution_id']);
        });

        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('industry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('published')->index();
            $table->unsignedSmallInteger('year');
            $table->string('client_label')->nullable();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'status', 'published_at']);
        });

        Schema::create('case_study_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->text('summary');
            $table->text('challenge');
            $table->text('strategy');
            $table->text('implementation');
            $table->json('results')->nullable();
            $table->json('gallery')->nullable();
            $table->string('illustrative_note')->nullable();
            $table->timestamps();
            $table->unique(['case_study_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
        });

        Schema::create('case_study_solution', function (Blueprint $table) {
            $table->foreignId('case_study_id')->constrained()->cascadeOnDelete();
            $table->foreignId('solution_id')->constrained()->cascadeOnDelete();
            $table->primary(['case_study_id', 'solution_id']);
        });

        Schema::create('article_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80);
            $table->json('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('article_categories')->nullOnDelete();
            $table->string('type', 40)->default('insight');
            $table->string('status')->default('published')->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('featured_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('reading_time')->default(5);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('updated_content_at')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'status', 'published_at']);
            $table->index(['site_id', 'type']);
        });

        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt');
            $table->json('body');
            $table->json('toc')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80);
            $table->json('name');
            $table->timestamps();
            $table->unique(['site_id', 'slug']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('revisionable_type');
            $table->unsignedBigInteger('revisionable_id');
            $table->string('locale', 10);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->string('message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['revisionable_type', 'revisionable_id', 'locale', 'version'], 'content_revision_unique');
            $table->index(['revisionable_type', 'revisionable_id']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('role');
            $table->string('department', 80)->nullable();
            $table->json('bio')->nullable();
            $table->foreignId('photo_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('is_leadership')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->json('social')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'is_leadership', 'sort']);
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->json('title');
            $table->json('description');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('department', 80);
            $table->string('location', 120);
            $table->string('employment_type', 40)->default('full_time');
            $table->string('status')->default('open')->index();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'status', 'posted_at']);
        });

        Schema::create('job_posting_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->string('slug');
            $table->text('summary');
            $table->json('description');
            $table->json('responsibilities')->nullable();
            $table->json('requirements')->nullable();
            $table->json('benefits')->nullable();
            $table->timestamps();
            $table->unique(['job_posting_id', 'locale']);
            $table->unique(['site_id', 'locale', 'slug']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->foreignId('resume_media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('portfolio_url', 1000)->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60)->default('corporate');
            $table->unsignedSmallInteger('year');
            $table->json('title');
            $table->json('description')->nullable();
            $table->foreignId('media_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_illustrative')->default(true);
            $table->timestamps();
            $table->index(['site_id', 'category', 'year']);
        });

        Schema::create('financial_highlights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->json('label');
            $table->string('value', 40);
            $table->string('unit', 20)->nullable();
            $table->string('note')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_illustrative')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_highlights');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_posting_translations');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('article_translations');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
        Schema::dropIfExists('case_study_solution');
        Schema::dropIfExists('case_study_translations');
        Schema::dropIfExists('case_studies');
        Schema::dropIfExists('industry_solution');
        Schema::dropIfExists('industry_translations');
        Schema::dropIfExists('industries');
        Schema::dropIfExists('solution_translations');
        Schema::dropIfExists('solutions');
    }
};
