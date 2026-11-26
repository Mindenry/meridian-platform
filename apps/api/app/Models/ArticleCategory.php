<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'slug', 'name', 'sort'];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function labelFor(string $locale, string $fallback = 'en'): string
    {
        return $this->name[$locale] ?? $this->name[$fallback] ?? $this->slug;
    }
}
