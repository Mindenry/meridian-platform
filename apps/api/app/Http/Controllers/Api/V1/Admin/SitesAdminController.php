<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Role;
use App\Models\Site;
use App\Models\SiteLocale;
use App\Models\WorkflowDefinition;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SitesAdminController extends Controller
{
    public function __construct(protected SiteContext $context, protected AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $sites = $user->accessibleSites();

        return response()->json(['data' => $sites->map(fn (Site $site) => [
            'id' => $site->id,
            'name' => $site->name,
            'slug' => $site->slug,
            'domain' => $site->domain,
            'default_locale' => $site->default_locale,
            'enabled_locales' => $site->enabled_locales,
            'is_primary' => $site->is_primary,
            'pages_count' => Page::forSite($site)->count(),
            'published_count' => Page::forSite($site)->where('status', 'published')->count(),
            'users_count' => DB::table('role_user')->where('site_id', $site->id)->distinct('user_id')->count('user_id'),
            'created_at' => $site->created_at?->toIso8601String(),
        ])->all()]);
    }

    public function show(Request $request, Site $site): JsonResponse
    {
        abort_unless($request->user()->belongsToSite($site), 403);

        return response()->json(['data' => [
            'id' => $site->id,
            'name' => $site->name,
            'legal_name' => $site->legal_name,
            'slug' => $site->slug,
            'domain' => $site->domain,
            'default_locale' => $site->default_locale,
            'enabled_locales' => $site->enabled_locales,
            'brand' => $site->brand,
            'theme' => $site->theme,
            'seo_defaults' => $site->seo_defaults,
            'settings' => $site->settings,
            'locales' => $site->locales()->orderBy('sort')->get(),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $user->hasPermission('sites.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:sites,slug'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:sites,domain'],
            'default_locale' => ['required', 'in:th,en,zh-CN'],
            'enabled_locales' => ['required', 'array', 'min:1'],
            'enabled_locales.*' => ['in:th,en,zh-CN'],
            'brand' => ['nullable', 'array'],
            'theme' => ['nullable', 'array'],
            'seo_defaults' => ['nullable', 'array'],
        ]);

        abort_unless(in_array($data['default_locale'], $data['enabled_locales'], true), 422, 'The default locale must be enabled.');

        $site = DB::transaction(function () use ($data, $user) {
            $site = Site::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'slug' => $data['slug'],
                'domain' => $data['domain'] ?? null,
                'default_locale' => $data['default_locale'],
                'enabled_locales' => $data['enabled_locales'],
                'brand' => $data['brand'] ?? null,
                'theme' => $data['theme'] ?? null,
                'seo_defaults' => $data['seo_defaults'] ?? null,
                'settings' => ['workflow' => ['require_approval' => true]],
                'is_primary' => false,
            ]);

            foreach ($data['enabled_locales'] as $index => $locale) {
                SiteLocale::create([
                    'site_id' => $site->id,
                    'locale' => $locale,
                    'label' => config('meridian.locales')[$locale]['label'] ?? strtoupper($locale),
                    'is_default' => $locale === $data['default_locale'],
                    'is_enabled' => true,
                    'sort' => $index,
                ]);
            }

            WorkflowDefinition::create([
                'site_id' => $site->id,
                'name' => 'Default editorial workflow',
                'steps' => [['name' => 'Editorial approval', 'permission' => 'pages.approve']],
                'is_default' => true,
            ]);

            $role = Role::where('slug', 'site_admin')->first();
            if ($role) {
                DB::table('role_user')->insert([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'site_id' => $site->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $site;
        });

        $this->audit->log('site.created', "Created website {$site->name}", [
            'site_id' => $site->id,
            'subject_type' => 'site',
            'subject_id' => $site->id,
        ]);

        return response()->json(['data' => ['id' => $site->id, 'slug' => $site->slug], 'message' => 'Website created.'], 201);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        abort_unless($request->user()->belongsToSite($site), 403);
        abort_unless($request->user()->hasPermission('settings.manage', $site), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:sites,domain,'.$site->id],
            'default_locale' => ['sometimes', 'in:th,en,zh-CN'],
            'enabled_locales' => ['sometimes', 'array', 'min:1'],
            'enabled_locales.*' => ['in:th,en,zh-CN'],
            'brand' => ['nullable', 'array'],
            'theme' => ['nullable', 'array'],
            'seo_defaults' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ]);

        if (isset($data['enabled_locales'])) {
            abort_unless(in_array($data['default_locale'] ?? $site->default_locale, $data['enabled_locales'], true), 422, 'The default locale must be enabled.');

            foreach ($data['enabled_locales'] as $index => $locale) {
                SiteLocale::updateOrCreate(
                    ['site_id' => $site->id, 'locale' => $locale],
                    [
                        'label' => config('meridian.locales')[$locale]['label'] ?? strtoupper($locale),
                        'is_default' => $locale === ($data['default_locale'] ?? $site->default_locale),
                        'is_enabled' => true,
                        'sort' => $index,
                    ]
                );
            }

            SiteLocale::forSite($site)->whereNotIn('locale', $data['enabled_locales'])->update(['is_enabled' => false]);
        }

        $site->update(array_intersect_key($data, array_flip([
            'name', 'legal_name', 'domain', 'default_locale', 'enabled_locales', 'brand', 'theme', 'seo_defaults', 'settings',
        ])));

        $this->audit->log('site.updated', "Updated website settings for {$site->name}", ['site_id' => $site->id]);

        return response()->json(['message' => 'Website settings saved.']);
    }
}
