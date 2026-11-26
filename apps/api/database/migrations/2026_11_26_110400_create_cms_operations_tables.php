<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);
            $table->string('name');
            $table->string('locale', 10);
            $table->timestamps();
            $table->unique(['site_id', 'key', 'locale']);
        });

        Schema::create('navigation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_items')->nullOnDelete();
            $table->json('label');
            $table->string('url', 1000)->nullable();
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->string('target', 20)->default('_self');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['navigation_menu_id', 'parent_id', 'sort']);
        });

        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('schema');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['site_id', 'key']);
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->json('meta')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('spam_score')->default(0);
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['site_id', 'created_at']);
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('form_submissions')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('company')->nullable();
            $table->text('message')->nullable();
            $table->string('source', 80)->default('contact');
            $table->string('status')->default('new')->index();
            $table->string('priority', 20)->default('normal');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['site_id', 'status', 'priority']);
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('steps');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('workflow_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_definition_id')->nullable()->constrained()->nullOnDelete();
            $table->string('workflowable_type');
            $table->unsignedBigInteger('workflowable_id');
            $table->string('status')->default('in_review')->index();
            $table->unsignedSmallInteger('current_step')->default(0);
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['workflowable_type', 'workflowable_id']);
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_instance_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step');
            $table->string('step_name');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 30);
            $table->text('note')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('event', 60)->default('page_view');
            $table->string('path', 1000);
            $table->string('locale', 10)->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('source', 120)->nullable();
            $table->string('device', 30)->nullable();
            $table->string('country', 5)->nullable();
            $table->json('meta')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['site_id', 'occurred_at']);
            $table->index(['site_id', 'event', 'occurred_at']);
            $table->index(['site_id', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_instances');
        Schema::dropIfExists('workflow_definitions');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
        Schema::dropIfExists('navigation_items');
        Schema::dropIfExists('navigation_menus');
    }
};
