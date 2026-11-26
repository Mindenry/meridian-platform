<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteLocale extends Model
{
    protected $fillable = ['site_id', 'locale', 'label', 'is_default', 'is_enabled', 'sort'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_enabled' => 'boolean',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
