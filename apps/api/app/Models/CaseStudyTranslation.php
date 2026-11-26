<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudyTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'case_study_id', 'site_id', 'locale', 'title', 'slug', 'summary', 'challenge',
        'strategy', 'implementation', 'results', 'gallery', 'illustrative_note',
    ];

    protected function casts(): array
    {
        return [
            'results' => 'array',
            'gallery' => 'array',
        ];
    }

    public function caseStudy(): BelongsTo
    {
        return $this->belongsTo(CaseStudy::class);
    }
}
