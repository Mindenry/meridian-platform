<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoAudit extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'score', 'summary', 'checked_at'];

    protected function casts(): array
    {
        return ['summary' => 'array', 'checked_at' => 'datetime', 'score' => 'integer'];
    }

    public function issues(): HasMany
    {
        return $this->hasMany(SeoIssue::class);
    }
}
