<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

class FinancialHighlight extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'year', 'label', 'value', 'unit', 'note', 'sort', 'is_illustrative'];

    protected function casts(): array
    {
        return ['label' => 'array', 'is_illustrative' => 'boolean', 'year' => 'integer'];
    }
}
