<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = ['page_id', 'site_id', 'locale', 'title', 'slug', 'excerpt', 'blocks_version'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
