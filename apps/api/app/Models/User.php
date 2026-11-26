<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'job_title', 'phone', 'locale', 'avatar_path',
        'is_active', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $permissionCache = [];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('site_id')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->contains('slug', 'super_admin');
    }

    /**
     * Sites the user can reach. Super administrators see every site.
     */
    public function accessibleSites(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Site::orderBy('name')->get();
        }

        $siteIds = $this->roles()
            ->whereNotNull('role_user.site_id')
            ->pluck('role_user.site_id')
            ->unique();

        return Site::whereIn('id', $siteIds)->orderBy('name')->get();
    }

    public function belongsToSite(Site|int $site): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->roles()->where('role_user.site_id', $siteId)->exists();
    }

    public function rolesForSite(Site|int|null $site = null): Collection
    {
        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->roles()
            ->when($siteId !== null, fn ($query) => $query->where('role_user.site_id', $siteId))
            ->get();
    }

    public function hasPermission(string $permission, Site|int|null $site = null): bool
    {
        $siteId = $site instanceof Site ? $site->id : $site;
        $cacheKey = $permission.':'.($siteId ?? 'any');

        if (array_key_exists($cacheKey, $this->permissionCache)) {
            return $this->permissionCache[$cacheKey];
        }

        if ($this->isSuperAdmin()) {
            return $this->permissionCache[$cacheKey] = true;
        }

        $allowed = $this->roles()
            ->when($siteId !== null, fn ($query) => $query->where('role_user.site_id', $siteId))
            ->whereHas('permissions', fn ($query) => $query->where('slug', $permission))
            ->exists();

        return $this->permissionCache[$cacheKey] = $allowed;
    }

    public function permissionSlugs(Site|int|null $site = null): Collection
    {
        if ($this->isSuperAdmin()) {
            return Permission::pluck('slug');
        }

        $siteId = $site instanceof Site ? $site->id : $site;

        return $this->roles()
            ->when($siteId !== null, fn ($query) => $query->where('role_user.site_id', $siteId))
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values();
    }

    public function assignRole(string $roleSlug, ?Site $site = null): void
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();

        $this->roles()->syncWithoutDetaching([
            $role->id => ['site_id' => $site?->id],
        ]);

        $this->unsetRelation('roles');
        $this->permissionCache = [];
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
