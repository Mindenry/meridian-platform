<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\PermissionRegistry;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessAdminController extends Controller
{
    public function __construct(protected SiteContext $context, protected AuditLogger $audit) {}

    public function users(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        $users = User::with(['roles'])
            ->where(function ($query) use ($site) {
                $query->whereHas('roles', fn ($role) => $role->where('role_user.site_id', $site->id))
                    ->orWhereHas('roles', fn ($role) => $role->where('slug', 'super_admin'));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'initials' => $user->initials(),
                'is_super_admin' => $user->isSuperAdmin(),
                'roles' => $user->roles->filter(fn ($role) => $role->pivot->site_id === null || (int) $role->pivot->site_id === $site->id)
                    ->map(fn ($role) => ['id' => $role->id, 'slug' => $role->slug, 'name' => $role->name])
                    ->values()->all(),
            ]);

        return response()->json([
            'data' => $users,
            'roles' => Role::withCount('permissions')->orderByDesc('level')->get()->map(fn (Role $role) => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'level' => $role->level,
                'permissions_count' => $role->permissions_count,
                'permissions' => $role->permissions->pluck('slug')->values()->all(),
            ])->all(),
            'permissions' => Permission::orderBy('group')->orderBy('name')->get()->groupBy('group')->map(fn ($items) => $items->map(fn ($permission) => [
                'slug' => $permission->slug, 'name' => $permission->name,
            ])->values()->all())->all(),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('users.manage', $site->id), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'role_slug' => ['required', 'exists:roles,slug'],
            'locale' => ['nullable', 'in:th,en,zh-CN'],
        ]);

        if ($data['role_slug'] === 'super_admin' && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only a super administrator can grant super administrator access.');
        }

        $password = Str::password(16, symbols: false);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'job_title' => $data['job_title'] ?? null,
            'locale' => $data['locale'] ?? $site->default_locale,
            'password' => $password,
            'is_active' => true,
        ]);

        $role = Role::where('slug', $data['role_slug'])->firstOrFail();
        DB::table('role_user')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'site_id' => $role->slug === 'super_admin' ? null : $site->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log('user.invited', "Invited {$user->name} as {$role->name}", [
            'subject_type' => 'user',
            'subject_id' => $user->id,
        ]);

        return response()->json([
            'data' => ['id' => $user->id],
            'temporary_password' => $password,
            'message' => 'Team member created. Deliver the temporary password through a secure channel; it will not be shown again.',
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('users.manage', $site->id), 403);
        abort_if($user->id === $request->user()->id && $request->boolean('is_active') === false, 422, 'You cannot deactivate your own account.');

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'is_active' => ['sometimes', 'boolean'],
            'role_slugs' => ['sometimes', 'array'],
            'role_slugs.*' => ['exists:roles,slug'],
        ]);

        if (array_key_exists('role_slugs', $data)) {
            if (in_array('super_admin', $data['role_slugs'], true) && ! $request->user()->isSuperAdmin()) {
                abort(403, 'Only a super administrator can grant super administrator access.');
            }

            if ($user->id === $request->user()->id && $user->isSuperAdmin() && ! in_array('super_admin', $data['role_slugs'], true)) {
                abort(422, 'You cannot remove your own super administrator access.');
            }

            DB::transaction(function () use ($user, $site, $data) {
                DB::table('role_user')->where('user_id', $user->id)->where('site_id', $site->id)->delete();

                foreach ($data['role_slugs'] as $slug) {
                    $role = Role::where('slug', $slug)->first();
                    if (! $role) {
                        continue;
                    }

                    if ($role->slug === 'super_admin') {
                        if (! DB::table('role_user')->where('user_id', $user->id)->where('role_id', $role->id)->whereNull('site_id')->exists()) {
                            DB::table('role_user')->insert([
                                'user_id' => $user->id, 'role_id' => $role->id, 'site_id' => null,
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    } elseif (! DB::table('role_user')->where('user_id', $user->id)->where('role_id', $role->id)->where('site_id', $site->id)->exists()) {
                        DB::table('role_user')->insert([
                            'user_id' => $user->id, 'role_id' => $role->id, 'site_id' => $site->id,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }

                $user->unsetRelation('roles');
            });
        }

        $user->fill(array_intersect_key($data, array_flip(['name', 'job_title', 'is_active'])));
        $user->save();

        $this->audit->log('user.updated', "Updated access for {$user->name}", [
            'subject_type' => 'user',
            'subject_id' => $user->id,
            'meta' => ['roles' => $data['role_slugs'] ?? null],
        ]);

        return response()->json(['message' => 'Team member updated.']);
    }

    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => Role::with('permissions')->orderByDesc('level')->get()->map(fn (Role $role) => [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'level' => $role->level,
                'is_system' => $role->is_system,
                'permissions' => $role->permissions->pluck('slug')->values()->all(),
            ])->all(),
            'catalogue' => collect(PermissionRegistry::permissions())->groupBy('group')->all(),
        ]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(10, (int) $request->query('per_page', 30)));

        $paginator = AuditLog::where('site_id', $site->id)
            ->with('user')
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->when($request->query('user_id'), fn ($q, $userId) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user' => $log->user?->name ?? 'System',
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'meta' => $log->meta,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
        ]);
    }
}
