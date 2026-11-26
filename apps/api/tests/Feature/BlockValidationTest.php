<?php

use App\Models\Page;
use Laravel\Sanctum\Sanctum;

it('rejects an unknown block type', function () {
    Sanctum::actingAs($this->admin);

    $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Unknown block page',
            'slug' => 'pest-unknown-block',
            'locale' => 'en',
            'blocks' => [['type' => 'arbitrary_script', 'props' => []]],
        ])->assertStatus(422);
});

it('rejects an unsafe javascript call-to-action URL', function () {
    Sanctum::actingAs($this->admin);

    $blocks = heroBlock();
    $blocks[0]['props']['primary_cta']['url'] = 'javascript:alert(1)';

    $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Unsafe URL page',
            'slug' => 'pest-unsafe-url',
            'locale' => 'en',
            'blocks' => $blocks,
        ])->assertStatus(422);
});

it('sanitises scripts from a safe HTML embed block', function () {
    Sanctum::actingAs($this->admin);

    $response = $this->withHeaders(adminHeaders($this))
        ->postJson('/api/v1/admin/pages', [
            'title' => 'Safe embed page',
            'slug' => 'pest-safe-embed',
            'locale' => 'en',
            'blocks' => [
                ['type' => 'rich_text', 'props' => ['heading' => 'Sanitised', 'body' => '<p>Safe copy</p>']],
                ['type' => 'html_embed', 'props' => ['html' => '<p>Allowed</p><script>alert(1)</script><iframe src="https://evil.example"></iframe>']],
            ],
        ])->assertCreated();

    $page = Page::find($response->json('data.id'));
    $html = $page->blocks()->where('type', 'html_embed')->firstOrFail()->props['html'];

    expect($html)->toContain('Allowed');
    expect($html)->not->toContain('<script');
    expect($html)->not->toContain('evil.example');
});
