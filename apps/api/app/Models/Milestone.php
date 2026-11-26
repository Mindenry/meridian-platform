<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'year', 'title', 'description', 'sort'];

    protected function casts(): array
    {
        return ['title' => 'array', 'description' => 'array', 'year' => 'integer'];
    }
}
