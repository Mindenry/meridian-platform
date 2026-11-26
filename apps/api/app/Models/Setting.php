<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'group', 'key', 'value', 'is_public'];

    protected function casts(): array
    {
        return ['value' => 'array', 'is_public' => 'boolean'];
    }
}
