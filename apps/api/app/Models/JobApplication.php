<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'job_posting_id', 'name', 'email', 'phone', 'resume_media_id',
        'cover_letter', 'portfolio_url', 'status', 'consent_at', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime'];
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'resume_media_id');
    }
}
