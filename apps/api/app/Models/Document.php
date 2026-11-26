<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'category', 'year', 'title', 'description', 'media_id',
        'published_at', 'sort', 'is_illustrative',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'published_at' => 'datetime',
            'is_illustrative' => 'boolean',
            'year' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_id');
    }
}
