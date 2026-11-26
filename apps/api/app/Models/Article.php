<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    public const TYPES = ['insight', 'news', 'technical', 'announcement', 'report'];

    protected $fillable = [
        'site_id', 'category_id', 'type', 'status', 'author_id', 'featured_media_id',
        'is_featured', 'reading_time', 'view_count', 'published_at', 'updated_content_at',
        'custom_fields', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'updated_content_at' => 'datetime',
            'reading_time' => 'integer',
            'view_count' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ArticleTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMetadata::class, 'seoable');
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(ContentRevision::class, 'revisionable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
