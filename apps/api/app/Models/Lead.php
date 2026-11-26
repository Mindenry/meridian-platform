<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use BelongsToSite, SoftDeletes;

    protected $fillable = [
        'site_id', 'submission_id', 'name', 'email', 'phone', 'company', 'message',
        'source', 'status', 'priority', 'owner_id', 'follow_up_at', 'notes',
        'last_activity_at', 'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(FormSubmission::class, 'submission_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }
}
