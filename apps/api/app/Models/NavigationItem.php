<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    protected $fillable = [
        'navigation_menu_id', 'parent_id', 'label', 'url', 'page_id', 'target', 'sort', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['label' => 'array', 'is_visible' => 'boolean'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function labelFor(string $locale, string $fallback = 'en'): string
    {
        return $this->label[$locale] ?? $this->label[$fallback] ?? '';
    }
}
