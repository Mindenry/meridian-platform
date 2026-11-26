<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MediaCollection extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'name', 'slug', 'description'];

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class, 'media_collection_items');
    }
}
