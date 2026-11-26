<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id', 'workflow_definition_id', 'workflowable_type', 'workflowable_id',
        'status', 'current_step', 'submitted_by', 'submitted_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }
}
