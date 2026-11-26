<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMember extends Model
{
    use BelongsToSite, SoftDeletes;

    protected $fillable = [
        'site_id', 'name', 'role', 'department', 'bio', 'photo_media_id',
        'is_leadership', 'sort', 'social',
    ];

    protected function casts(): array
    {
        return [
            'role' => 'array',
            'bio' => 'array',
            'social' => 'array',
            'is_leadership' => 'boolean',
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_media_id');
    }

    public function label(string $field, string $locale, string $fallback = 'en'): ?string
    {
        $values = $this->{$field};

        return is_array($values) ? ($values[$locale] ?? $values[$fallback] ?? null) : $values;
    }
}
