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

class Solution extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    protected $fillable = ['site_id', 'key', 'icon', 'status', 'sort', 'featured_media_id'];

    public function translations(): HasMany
    {
        return $this->hasMany(SolutionTranslation::class);
    }

    public function industries(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class);
    }

    public function caseStudies(): BelongsToMany
    {
        return $this->belongsToMany(CaseStudy::class);
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
