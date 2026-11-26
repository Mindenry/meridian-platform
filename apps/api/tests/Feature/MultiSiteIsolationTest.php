<?php

use App\Models\Page;
use App\Models\Site;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('does not let a user from one website read another website through the admin API', function () {
    $energy = Site::where('slug', 'meridian-energy')->firstOrFail();
    $energyAdmin = User::where('email', 'admin@meridian-energy.example')->firstOrFail();

    Sanctum::actingAs($energyAdmin);

    $this->withHeaders(['X-Site-Id' => (string) $this->site->id])
        ->getJson('/api/v1/admin/pages')
        ->assertStatus(403);

    $expected = Page::where('site_id', $energy->id)->count();

    expect($expected)->toBeGreaterThan(0);

    $this->withHeaders(['X-Site-Id' => (string) $energy->id])
        ->getJson('/api/v1/admin/pages')
        ->assertOk()
        ->assertJsonPath('meta.total', $expected);
});

it('serves the correct website for a public site header', function () {
    $this->getJson('/api/v1/public/bootstrap?locale=en', ['X-Site' => 'meridian'])
        ->assertOk()
        ->assertJsonPath('data.site.slug', 'meridian');

    $this->getJson('/api/v1/public/bootstrap?locale=en', ['X-Site' => 'meridian-energy'])
        ->assertOk()
        ->assertJsonPath('data.site.slug', 'meridian-energy');
});

it('keeps public content scoped to the active website', function () {
    // A page exists on Meridian, not on the energy site.
    $this->getJson('/api/v1/public/pages/about?locale=en', ['X-Site' => 'meridian'])
        ->assertOk();

    $this->getJson('/api/v1/public/pages/about?locale=en', ['X-Site' => 'meridian-energy'])
        ->assertNotFound();
});
