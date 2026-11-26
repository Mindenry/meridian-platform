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

class Industry extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    protected $fillable = ['site_id', 'key', 'icon', 'status', 'sort', 'featured_media_id'];

    public function translations(): HasMany
    {
        return $this->hasMany(IndustryTranslation::class);
    }

    public function solutions(): BelongsToMany
    {
        return $this->belongsToMany(Solution::class);
    }

    public function caseStudies(): HasMany
    {
        return $this->hasMany(CaseStudy::class);
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_media_id');
    }

    public function seo(): MorphMany
    {
        return $this->morphMany(SeoMetadata::class, 'seoable');
    }
}
