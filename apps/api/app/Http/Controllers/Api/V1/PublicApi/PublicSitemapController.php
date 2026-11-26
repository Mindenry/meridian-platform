<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\ContentPresenter;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSitemapController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected ContentPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json([
            'data' => collect($site->activeLocales())->map(fn ($locale) => [
                'locale' => $locale,
                'route' => config('meridian.locales')[$locale]['route'] ?? strtolower($locale),
                'url' => rtrim((string) config('meridian.website_url'), '/').'/sitemaps/'.(config('meridian.locales')[$locale]['route'] ?? strtolower($locale)).'.xml',
            ])->values()->all(),
        ]);
    }

    public function locale(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $base = rtrim((string) config('meridian.website_url'), '/');

        $urls = collect($this->presenter->sitemap($site, $locale))
            ->map(fn ($entry) => array_merge($entry, ['loc' => $base.$entry['loc']]))
            ->all();

        return response()->json([
            'data' => [
                'locale' => $locale,
                'urls' => $urls,
            ],
        ]);
    }
}
