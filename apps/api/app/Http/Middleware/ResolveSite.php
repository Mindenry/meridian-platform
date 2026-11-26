<?php

namespace App\Http\Middleware;

use App\Cms\SiteContext;
use App\Models\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveSite
{
    public function __construct(protected SiteContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $site = null;
        $user = $request->user('sanctum') ?? $request->user();

        $siteId = $request->header('X-Site-Id');
        $siteKey = $request->header('X-Site') ?? $request->query('site');

        if ($siteId && $user) {
            $site = $user->accessibleSites()->firstWhere('id', (int) $siteId);
            abort_unless($site, 403, 'You do not have access to this website.');
        }

        if (! $site && $siteKey) {
            $site = Site::where('slug', $siteKey)
                ->orWhere('domain', $siteKey)
                ->first();
        }

        if (! $site && $request->is('api/v1/admin/*') && $user) {
            $site = $user->accessibleSites()->first();
        }

        if (! $site) {
            $site = Site::where('is_primary', true)->first() ?? Site::orderBy('id')->first();
        }

        abort_unless($site, 404, 'No website is configured.');

        $this->context->setSite($site);

        $locale = $request->query('locale') ?? $request->header('X-Locale');
        $this->context->setLocale($this->context->normalizeLocale($locale));

        return $next($request);
    }
}
