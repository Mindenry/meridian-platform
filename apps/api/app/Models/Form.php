<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'key', 'name', 'description', 'schema', 'settings', 'is_active'];

    protected function casts(): array
    {
        return ['schema' => 'array', 'settings' => 'array', 'is_active' => 'boolean'];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class);
    }
}
