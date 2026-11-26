<?php

namespace App\Models\Concerns;

use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSite
{
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForSite(Builder $query, Site|int $site): Builder
    {
        return $query->where($this->getTable().'.site_id', $site instanceof Site ? $site->id : $site);
    }
}
