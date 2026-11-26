<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoIssue extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'seo_audit_id', 'seoable_type', 'seoable_id', 'locale', 'code', 'severity',
        'url', 'title', 'explanation', 'recommendation', 'status', 'last_checked_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['last_checked_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(SeoAudit::class, 'seo_audit_id');
    }
}
