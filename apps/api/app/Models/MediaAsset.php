<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    use BelongsToSite, SoftDeletes;

    protected $fillable = [
        'site_id', 'folder_id', 'uploaded_by', 'disk', 'path', 'filename', 'original_name',
        'mime_type', 'extension', 'size', 'width', 'height', 'alt', 'caption', 'credit',
        'copyright', 'license', 'source_url', 'focal_x', 'focal_y', 'variants', 'checksum',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'alt' => 'array',
            'caption' => 'array',
            'variants' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'focal_x' => 'float',
            'focal_y' => 'float',
            'usage_count' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(MediaCollection::class, 'media_collection_items');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function getUrlAttribute(): string
    {
        return $this->urlForPath($this->path);
    }

    public function urlForPath(string $path): string
    {
        if ($this->disk === 's3') {
            return Storage::disk('s3')->url($path);
        }

        $base = rtrim((string) config('media.public_url', config('app.url').'/storage'), '/');

        return $base.'/'.ltrim($path, '/');
    }

    public function variantUrls(): array
    {
        return collect($this->variants ?? [])
            ->map(fn (array $variant, string $name) => [
                'name' => $name,
                'url' => $this->urlForPath($variant['path']),
                'width' => $variant['width'] ?? null,
                'height' => $variant['height'] ?? null,
            ])
            ->values()
            ->all();
    }

    public function altFor(string $locale, ?string $fallback = 'en'): string
    {
        return $this->alt[$locale] ?? $this->alt[$fallback] ?? $this->original_name;
    }

    public function toPublicArray(string $locale = 'th'): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'alt' => $this->altFor($locale),
            'caption' => $this->caption[$locale] ?? null,
            'credit' => $this->credit,
            'width' => $this->width,
            'height' => $this->height,
            'focal_x' => $this->focal_x,
            'focal_y' => $this->focal_y,
            'variants' => $this->variantUrls(),
        ];
    }
}
