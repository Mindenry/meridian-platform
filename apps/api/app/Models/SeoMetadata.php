<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    use BelongsToSite;

    protected $table = 'seo_metadata';

    protected $fillable = [
        'site_id', 'seoable_type', 'seoable_id', 'locale', 'title', 'description', 'canonical',
        'focus_keyword', 'og_title', 'og_description', 'og_image_id', 'robots', 'schema_type',
        'schema', 'breadcrumbs',
    ];

    protected function casts(): array
    {
        return [
            'schema' => 'array',
            'breadcrumbs' => 'array',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    public function toPublicArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'focus_keyword' => $this->focus_keyword,
            'og' => [
                'title' => $this->og_title ?: $this->title,
                'description' => $this->og_description ?: $this->description,
                'image' => $this->ogImage?->url,
            ],
            'robots' => $this->robots,
            'schema_type' => $this->schema_type,
            'schema' => $this->schema,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }
}
