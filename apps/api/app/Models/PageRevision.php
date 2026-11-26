<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageRevision extends Model
{
    public $timestamps = false;

    protected $fillable = ['page_id', 'locale', 'user_id', 'version', 'snapshot', 'message', 'created_at'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
