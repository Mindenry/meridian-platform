<?php

it('searches only the requested locale and groups results', function () {
    $response = $this->getJson('/api/v1/public/search?q='.urlencode('ระบบ').'&locale=th', ['X-Site' => 'meridian'])
        ->assertOk();

    expect($response->json('data.total'))->toBeGreaterThan(0);
    expect(collect($response->json('data.groups'))->pluck('type'))->toContain('solution');
    expect(collect($response->json('data.groups'))->flatMap(fn ($group) => $group['items'])
        ->every(fn ($item) => str_starts_with($item['url'], '/th/')))->toBeTrue();
});

it('does not expose admin or draft-only content through public search', function () {
    $response = $this->getJson('/api/v1/public/search?q=admin&locale=en', ['X-Site' => 'meridian'])->assertOk();

    $urls = collect($response->json('data.groups'))->flatMap(fn ($group) => $group['items'])->pluck('url');
    expect($urls->contains(fn ($url) => str_contains($url, '/admin')))->toBeFalse();
});
