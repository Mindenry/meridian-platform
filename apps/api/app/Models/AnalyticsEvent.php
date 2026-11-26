<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use BelongsToSite;

    public $timestamps = false;

    protected $fillable = [
        'site_id', 'event', 'path', 'locale', 'session_hash', 'referrer', 'source',
        'device', 'country', 'meta', 'is_demo', 'occurred_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_demo' => 'boolean',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
