<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentRevision extends Model
{
    use BelongsToSite;

    public $timestamps = false;

    protected $fillable = [
        'site_id', 'revisionable_type', 'revisionable_id', 'locale', 'user_id', 'version',
        'snapshot', 'message', 'created_at',
    ];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'created_at' => 'datetime'];
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
