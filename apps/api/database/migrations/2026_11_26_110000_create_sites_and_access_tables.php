<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('default_locale', 10)->default('th');
            $table->json('enabled_locales');
            $table->json('brand')->nullable();
            $table->json('theme')->nullable();
            $table->json('seo_defaults')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('label');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['site_id', 'locale']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('job_title')->nullable()->after('email');
            $table->string('phone', 40)->nullable()->after('job_title');
            $table->string('locale', 10)->default('en')->after('phone');
            $table->string('avatar_path')->nullable()->after('locale');
            $table->boolean('is_active')->default(true)->after('avatar_path');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('level')->default(10);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group')->index();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'site_id']);
            $table->index(['user_id', 'site_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['notifiable_id', 'read_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['site_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('group')->default('general');
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['site_id', 'group', 'key']);
        });

        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('status')->default('disconnected');
            $table->text('credentials')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
            $table->unique(['site_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_settings');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['job_title', 'phone', 'locale', 'avatar_path', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('site_locales');
        Schema::dropIfExists('sites');
    }
};
