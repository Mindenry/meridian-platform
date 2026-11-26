<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPostingTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'job_posting_id', 'site_id', 'locale', 'title', 'slug', 'summary', 'description',
        'responsibilities', 'requirements', 'benefits',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'array',
            'responsibilities' => 'array',
            'requirements' => 'array',
            'benefits' => 'array',
        ];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
