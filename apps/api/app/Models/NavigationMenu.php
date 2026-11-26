<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenu extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'key', 'name', 'locale'];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('sort');
    }
}
