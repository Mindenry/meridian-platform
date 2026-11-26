<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolutionTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'solution_id', 'site_id', 'locale', 'title', 'slug', 'tagline', 'overview',
        'problems', 'approach', 'capabilities', 'benefits',
    ];

    protected function casts(): array
    {
        return [
            'problems' => 'array',
            'approach' => 'array',
            'capabilities' => 'array',
            'benefits' => 'array',
        ];
    }

    public function solution(): BelongsTo
    {
        return $this->belongsTo(Solution::class);
    }
}
