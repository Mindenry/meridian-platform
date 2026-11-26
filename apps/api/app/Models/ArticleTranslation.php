<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    use BelongsToSite;

    protected $fillable = ['article_id', 'site_id', 'locale', 'title', 'slug', 'excerpt', 'body', 'toc'];

    protected function casts(): array
    {
        return ['body' => 'array', 'toc' => 'array'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
