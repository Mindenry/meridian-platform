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

class CaseStudy extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    protected $fillable = [
        'site_id', 'industry_id', 'status', 'year', 'client_label', 'featured_media_id',
        'published_at', 'is_featured', 'sort', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'year' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(CaseStudyTranslation::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMetadata::class, 'seoable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
