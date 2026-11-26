<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use App\Models\Concerns\HasLocalizedTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use BelongsToSite, HasLocalizedTranslations, SoftDeletes;

    protected $fillable = [
        'site_id', 'department', 'location', 'employment_type', 'status', 'posted_at',
        'closes_at', 'sort',
    ];

    protected function casts(): array
    {
        return ['posted_at' => 'datetime', 'closes_at' => 'datetime'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(JobPostingTranslation::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()));
    }
}
