<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSite;
use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'provider', 'status', 'credentials', 'meta', 'last_checked_at'];

    protected function casts(): array
    {
        return ['credentials' => 'encrypted:array', 'meta' => 'array', 'last_checked_at' => 'datetime'];
    }

    public function toSafeArray(): array
    {
        return [
            'provider' => $this->provider,
            'status' => $this->status,
            'meta' => $this->meta,
            'last_checked_at' => $this->last_checked_at,
            'configured' => ! empty($this->credentials),
        ];
    }
}
