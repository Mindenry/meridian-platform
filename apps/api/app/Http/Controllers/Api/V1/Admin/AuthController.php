<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->is_active || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The email address or password is incorrect.',
            ]);
        }

        if (! Auth::guard('web')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => 1,
        ], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages(['email' => 'Sign in failed. Please try again.']);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['data' => $this->userPayload($user)]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $site = app(SiteContext::class)->site();

        return response()->json(['data' => $this->userPayload($user, $site?->id)]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'locale' => ['nullable', 'in:th,en,zh-CN'],
        ]);

        $user->update($validated);

        return response()->json(['data' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Signed out.']);
    }

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->limit(30)->get()->map(fn ($notification) => [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $notifications,
            'unread' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markNotification(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllNotifications(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function userPayload(User $user, ?int $siteId = null): array
    {
        $sites = $user->accessibleSites();

        if (! $siteId && $sites->isNotEmpty()) {
            $siteId = $sites->first()->id;
        }

        $roles = $user->roles()->get()->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'slug' => $role->slug,
            'level' => $role->level,
            'site_id' => $role->pivot->site_id,
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'job_title' => $user->job_title,
            'phone' => $user->phone,
            'locale' => $user->locale,
            'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            'initials' => $user->initials(),
            'is_super_admin' => $user->isSuperAdmin(),
            'roles' => $roles->values()->all(),
            'permissions' => $user->permissionSlugs($siteId)->values()->all(),
            'sites' => $sites->map(fn ($site) => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'domain' => $site->domain,
                'default_locale' => $site->default_locale,
                'enabled_locales' => $site->enabled_locales,
            ])->values()->all(),
            'current_site_id' => $siteId,
            'notifications_unread' => $user->unreadNotifications()->count(),
        ];
    }
}
