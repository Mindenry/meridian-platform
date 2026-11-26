<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagePreviewToken extends Model
{
    use BelongsToSite;

    protected $fillable = ['page_id', 'site_id', 'token', 'locale', 'created_by', 'expires_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }
}
