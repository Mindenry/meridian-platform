<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledPublication extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'page_id', 'locale', 'publish_at', 'status', 'published_at', 'error', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
