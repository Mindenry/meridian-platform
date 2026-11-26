<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowDefinition extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'name', 'steps', 'is_default'];

    protected function casts(): array
    {
        return ['steps' => 'array', 'is_default' => 'boolean'];
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }
}
