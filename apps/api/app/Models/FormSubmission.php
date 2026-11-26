<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FormSubmission extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'form_id', 'data', 'meta', 'status', 'ip_address', 'user_agent',
        'spam_score', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'meta' => 'array',
            'is_demo' => 'boolean',
            'spam_score' => 'integer',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'submission_id');
    }
}
