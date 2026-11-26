<?php

use App\Models\Page;
use App\Models\SeoAudit;
use App\Models\SeoIssue;
use Laravel\Sanctum\Sanctum;

it('runs a deterministic SEO audit and exposes explainable issues', function () {
    Sanctum::actingAs($this->admin);

    $first = $this->withHeaders(adminHeaders($this))->postJson('/api/v1/admin/seo/audit')->assertOk();
    $scoreOne = $first->json('data.score');

    $second = $this->withHeaders(adminHeaders($this))->postJson('/api/v1/admin/seo/audit')->assertOk();
    $scoreTwo = $second->json('data.score');

    expect($scoreOne)->toBe($scoreTwo);
    expect($scoreOne)->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
    expect(SeoAudit::forSite($this->site)->count())->toBeGreaterThanOrEqual(2);
    expect(SeoIssue::forSite($this->site)->where('status', 'open')->count())->toBeGreaterThan(0);

    $this->withHeaders(adminHeaders($this))
        ->getJson('/api/v1/admin/seo/issues')
        ->assertOk()
        ->assertJsonStructure(['data' => [['code', 'severity', 'explanation', 'recommendation', 'last_checked_at']]]);
});

it('generates locale-aware canonical and hreflang output for a translated page', function () {
    $page = Page::forSite($this->site)->whereHas('translations', fn ($q) => $q->where('slug', 'about'))->firstOrFail();

    $response = $this->getJson('/api/v1/public/pages/about?locale=en', ['X-Site' => 'meridian'])->assertOk();
    $data = $response->json('data');

    expect($data['seo']['canonical'])->toContain('/en/about');
    expect(collect($data['alternates'])->pluck('locale'))->toContain('th')->toContain('en');
});

it('serves a sitemap payload with canonical public URLs', function () {
    $response = $this->getJson('/api/v1/public/sitemap?locale=th', ['X-Site' => 'meridian'])->assertOk();

    $urls = collect($response->json('data.urls'))->pluck('loc');
    expect($urls->contains(fn ($url) => str_ends_with($url, '/th')))->toBeTrue();
    expect($urls->contains(fn ($url) => str_contains($url, '/th/solutions/')))->toBeTrue();
});
