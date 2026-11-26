<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageBlock extends Model
{
    protected $fillable = [
        'page_id', 'locale', 'block_uid', 'type', 'sort_order', 'props',
        'is_visible_desktop', 'is_visible_tablet', 'is_visible_mobile', 'responsive',
    ];

    protected function casts(): array
    {
        return [
            'props' => 'array',
            'responsive' => 'array',
            'is_visible_desktop' => 'boolean',
            'is_visible_tablet' => 'boolean',
            'is_visible_mobile' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function toPublicArray(): array
    {
        return [
            'uid' => $this->block_uid,
            'type' => $this->type,
            'props' => $this->props,
            'responsive' => [
                'desktop' => $this->is_visible_desktop,
                'tablet' => $this->is_visible_tablet,
                'mobile' => $this->is_visible_mobile,
                'overrides' => $this->responsive ?? [],
            ],
        ];
    }
}
