<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'legal_name', 'slug', 'domain', 'default_locale', 'enabled_locales',
        'brand', 'theme', 'seo_defaults', 'settings', 'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'enabled_locales' => 'array',
            'brand' => 'array',
            'theme' => 'array',
            'seo_defaults' => 'array',
            'settings' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function locales(): HasMany
    {
        return $this->hasMany(SiteLocale::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    public function menus(): HasMany
    {
        return $this->hasMany(NavigationMenu::class);
    }

    public function activeLocales(): array
    {
        return $this->locales()
            ->where('is_enabled', true)
            ->orderBy('sort')
            ->pluck('locale')
            ->all();
    }

    public function localeLabel(string $locale): string
    {
        return $this->locales()->where('locale', $locale)->value('label') ?? strtoupper($locale);
    }
}
