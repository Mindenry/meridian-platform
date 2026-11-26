<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'site_id', 'parent_id', 'template', 'status', 'author_id', 'featured_media_id',
        'published_at', 'scheduled_at', 'expires_at', 'sort_order', 'is_home', 'lock_version',
        'custom_fields', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_home' => 'boolean',
            'lock_version' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    public function blocksFor(string $locale): HasMany
    {
        return $this->blocks()->where('locale', $locale);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMetadata::class, 'seoable');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest('version');
    }

    public function previewTokens(): HasMany
    {
        return $this->hasMany(PagePreviewToken::class);
    }

    public function scheduledPublications(): HasMany
    {
        return $this->hasMany(ScheduledPublication::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->isPast()
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function translationForLocale(string $locale, string $fallback = 'en'): ?PageTranslation
    {
        return $this->translationFor($locale, $fallback);
    }

    public function seoFor(string $locale): ?SeoMetadata
    {
        return $this->seo()->where('locale', $locale)->first()
            ?? $this->seo()->where('locale', 'en')->first();
    }

    public function alternates(string $siteUrl): array
    {
        return $this->translations->map(fn (PageTranslation $translation) => [
            'locale' => $translation->locale,
            'slug' => $translation->slug,
            'url' => rtrim($siteUrl, '/').'/'.$translation->locale.'/'.$translation->slug,
        ])->values()->all();
    }
}
