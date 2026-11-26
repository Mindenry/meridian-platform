<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Models\Site;
use App\Models\SiteLocale;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsAdminController extends Controller
{
    public function __construct(protected SiteContext $context, protected AuditLogger $audit) {}

    public function show(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json(['data' => [
            'site' => [
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
                'is_primary' => $site->is_primary,
            ],
            'locales' => $site->locales()->orderBy('sort')->get()->map(fn (SiteLocale $locale) => [
                'locale' => $locale->locale,
                'label' => $locale->label,
                'is_default' => $locale->is_default,
                'is_enabled' => $locale->is_enabled,
                'route' => config('meridian.locales')[$locale->locale]['route'] ?? strtolower($locale->locale),
            ])->all(),
            'locale_catalogue' => config('meridian.locales'),
            'integrations' => $this->integrations($site),
            'public_url' => rtrim((string) config('meridian.website_url'), '/'),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('settings.manage', $site->id), 403);

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

        $this->audit->log('settings.updated', 'Updated site settings', ['site_id' => $site->id]);

        return response()->json(['message' => 'Settings saved.']);
    }

    public function updateIntegration(Request $request, string $provider): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('integrations.manage', $site->id), 403);
        abort_unless(array_key_exists($provider, config('meridian.integrations')), 404);

        $data = $request->validate([
            'credentials' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $credentials = $data['credentials'] ?? [];
        $hasCredentials = collect($credentials)->filter(fn ($value) => ! empty($value))->isNotEmpty();

        $integration = IntegrationSetting::updateOrCreate(
            ['site_id' => $site->id, 'provider' => $provider],
            [
                'status' => $hasCredentials ? 'configured' : 'disconnected',
                'credentials' => $hasCredentials ? $credentials : null,
                'meta' => $data['meta'] ?? null,
                'last_checked_at' => now(),
            ]
        );

        $this->audit->log('integration.updated', "Updated {$provider} integration settings", ['site_id' => $site->id]);

        return response()->json([
            'data' => $integration->toSafeArray(),
            'message' => $hasCredentials
                ? 'Credentials stored. Live verification has not been performed; the connection remains unverified.'
                : 'Integration disconnected.',
        ]);
    }

    public function testIntegration(Request $request, string $provider): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('integrations.manage', $site->id), 403);
        abort_unless(array_key_exists($provider, config('meridian.integrations')), 404);

        $integration = IntegrationSetting::where('site_id', $site->id)->where('provider', $provider)->first();
        $definition = config('meridian.integrations')[$provider];
        $envPresent = collect($definition['env'] ?? [])->every(fn ($key) => ! empty(env($key)));

        $status = ($integration?->credentials || $envPresent) ? 'unverified' : 'disconnected';

        IntegrationSetting::updateOrCreate(
            ['site_id' => $site->id, 'provider' => $provider],
            ['status' => $status, 'last_checked_at' => now()]
        );

        return response()->json([
            'status' => $status,
            'message' => $status === 'unverified'
                ? 'Credentials are present, but no live API call was made. Complete OAuth in the provider console to verify.'
                : 'No credentials are configured. Add credentials or environment variables first.',
        ]);
    }

    protected function integrations(Site $site): array
    {
        $stored = IntegrationSetting::forSite($site)->get()->keyBy('provider');

        return collect(config('meridian.integrations'))->map(function (array $definition, string $provider) use ($stored) {
            $setting = $stored->get($provider);
            $envPresent = collect($definition['env'] ?? [])->every(fn ($key) => ! empty(env($key)));

            return [
                'provider' => $provider,
                'label' => $definition['label'],
                'status' => $setting?->status ?? ($envPresent ? 'unverified' : 'disconnected'),
                'configured' => (bool) $setting?->credentials || $envPresent,
                'meta' => $setting?->meta,
                'last_checked_at' => $setting?->last_checked_at?->toIso8601String(),
                'required_env' => $definition['env'] ?? [],
            ];
        })->values()->all();
    }
}
