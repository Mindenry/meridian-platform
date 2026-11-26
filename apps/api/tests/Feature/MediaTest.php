<?php

use App\Models\MediaAsset;
use App\Models\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('public');
    Sanctum::actingAs($this->admin);
});

it('validates and stores a real image upload with generated variants', function () {
    $file = UploadedFile::fake()->image('factory.jpg', 1600, 1000);

    $response = $this->withHeaders(adminHeaders($this))
        ->post('/api/v1/admin/media', [
            'files' => [$file],
            'alt' => ['en' => 'Industrial factory test image', 'th' => 'ภาพโรงงานทดสอบ'],
        ])
        ->assertCreated();

    $asset = MediaAsset::find($response->json('data.0.id'));
    expect($asset)->not->toBeNull();
    expect($asset->mime_type)->toBe('image/jpeg');
    expect($asset->width)->toBe(1600);
    expect($asset->alt)->toBeArray();
    expect($asset->variants)->toHaveKey('medium');

    Storage::disk('public')->assertExists($asset->path);
});

it('rejects a file whose content type is not allow-listed', function () {
    $file = UploadedFile::fake()->create('malware.php', 10, 'application/x-php');

    $this->withHeaders(adminHeaders($this))
        ->post('/api/v1/admin/media', ['files' => [$file]])
        ->assertStatus(422);
});

it('prevents deleting a media asset that is used by a page', function () {
    $file = UploadedFile::fake()->image('used.jpg', 800, 600);
    $upload = $this->withHeaders(adminHeaders($this))
        ->post('/api/v1/admin/media', ['files' => [$file]])
        ->assertCreated();
    $assetId = $upload->json('data.0.id');

    $page = Page::forSite($this->site)->where('is_home', true)->firstOrFail();
    $block = $page->blocks()->where('locale', 'en')->firstOrFail();
    $props = $block->props;
    $props['image'] = ['media_id' => $assetId, 'url' => 'http://localhost/storage/test.jpg', 'alt' => 'Used image'];
    $block->update(['props' => $props]);

    $this->withHeaders(adminHeaders($this))
        ->deleteJson("/api/v1/admin/media/{$assetId}")
        ->assertStatus(409)
        ->assertJsonPath('code', 'media_in_use');
});

it('deletes an unused asset', function () {
    $file = UploadedFile::fake()->image('unused.jpg', 800, 600);
    $upload = $this->withHeaders(adminHeaders($this))
        ->post('/api/v1/admin/media', ['files' => [$file]])
        ->assertCreated();
    $assetId = $upload->json('data.0.id');

    $this->withHeaders(adminHeaders($this))
        ->deleteJson("/api/v1/admin/media/{$assetId}")
        ->assertOk();

    expect(MediaAsset::find($assetId))->toBeNull();
});
