<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'industry_id', 'site_id', 'locale', 'title', 'slug', 'tagline', 'overview',
        'challenges', 'capabilities', 'stats',
    ];

    protected function casts(): array
    {
        return [
            'challenges' => 'array',
            'capabilities' => 'array',
            'stats' => 'array',
        ];
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }
}
