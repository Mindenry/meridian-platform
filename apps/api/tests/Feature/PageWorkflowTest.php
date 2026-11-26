<?php

use App\Models\Page;
use App\Models\ScheduledPublication;
use Laravel\Sanctum\Sanctum;

it('signs in with valid credentials and returns scoped permissions', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@meridian.example',
        'password' => 'Meridian!2026',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.email', 'admin@meridian.example')
        ->assertJsonPath('data.is_super_admin', true);

    expect($response->json('data.permissions'))->toContain('pages.publish');
});

it('rejects invalid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@meridian.example',
        'password' => 'wrong-password',
    ])->assertStatus(422);
});

it('creates, reviews, approves, and publishes a page end to end', function () {
    Sanctum::actingAs($this->admin);
    $slug = 'pest-publish-flow';

    $create = $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Pest publishing flow',
            'slug' => $slug,
            'locale' => 'en',
            'blocks' => heroBlock(),
            'seo' => ['title' => 'Pest publishing flow — Meridian', 'description' => 'A test page for the publishing workflow.'],
        ])
        ->assertCreated();

    $pageId = $create->json('data.id');

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/submit", ['note' => 'Ready for review'])
        ->assertOk()
        ->assertJsonPath('data.status', 'in_review');

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/approve", ['note' => 'Approved'])
        ->assertOk()
        ->assertJsonPath('data.status', 'approved');

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/publish")
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    $this->getJson("/api/v1/public/pages/{$slug}?locale=en", ['X-Site' => 'meridian'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Pest publishing flow')
        ->assertJsonPath('data.blocks.0.type', 'hero')
        ->assertJsonPath('data.seo.robots', 'index,follow');
});

it('serves a public job detail with schema', function () {
    $this->getJson('/api/v1/public/careers/senior-automation-engineer?locale=en', ['X-Site' => 'meridian'])
        ->assertOk()
        ->assertJsonPath('data.type', 'job')
        ->assertJsonPath('data.slug', 'senior-automation-engineer')
        ->assertJsonPath('data.is_fallback', false)
        ->assertJsonStructure(['data' => ['seo' => ['title', 'canonical', 'schema']]]);
});

it('prevents an editor without publish permission from publishing', function () {
    Sanctum::actingAs($this->editor);

    $create = $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Editor cannot publish',
            'slug' => 'pest-editor-cannot-publish',
            'locale' => 'en',
            'blocks' => heroBlock(),
        ])->assertCreated();

    $pageId = $create->json('data.id');

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/submit", ['note' => 'Please review'])
        ->assertOk();

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/publish")
        ->assertStatus(403);

    expect(Page::find($pageId)->status)->toBe(Page::STATUS_IN_REVIEW);
});

it('publishes a scheduled page exactly once', function () {
    Sanctum::actingAs($this->admin);
    $slug = 'pest-scheduled-once';

    $create = $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Scheduled once',
            'slug' => $slug,
            'locale' => 'en',
            'blocks' => heroBlock(),
        ])->assertCreated();

    $pageId = $create->json('data.id');
    $publishAt = now()->addDay();

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/schedule", ['publish_at' => $publishAt->toIso8601String()])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled');

    $this->travel(2)->days();
    $this->artisan('content:publish-scheduled', ['--sync' => true])->assertSuccessful();

    $page = Page::find($pageId)->fresh();
    expect($page->status)->toBe(Page::STATUS_PUBLISHED);
    $publishedAt = $page->published_at->toIso8601String();

    $this->artisan('content:publish-scheduled', ['--sync' => true])->assertSuccessful();
    expect($page->fresh()->published_at->toIso8601String())->toBe($publishedAt);
    expect(ScheduledPublication::where('page_id', $pageId)->where('status', 'published')->count())->toBe(1);
});

it('rejects publishing while a page is awaiting review', function () {
    Sanctum::actingAs($this->admin);
    $create = $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Blocked publish',
            'slug' => 'pest-blocked-publish',
            'locale' => 'en',
            'blocks' => heroBlock(),
        ])->assertCreated();

    $pageId = $create->json('data.id');
    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/submit")
        ->assertOk();

    $this->withHeaders(adminHeaders($this))
        ->postJson("/api/v1/admin/pages/{$pageId}/publish")
        ->assertStatus(422);
});
