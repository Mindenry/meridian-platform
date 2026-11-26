<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\ContentPresenter;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PagePreviewToken;
use App\Models\Redirect;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function __construct(
        protected ContentPresenter $presenter,
        protected SiteContext $context,
    ) {}

    public function home(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $page = Page::forSite($site)->published()->where('is_home', true)->first();

        abort_unless($page, 404, 'No homepage has been published.');

        return response()->json(['data' => $this->presenter->page($page, $locale)]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $page = Page::forSite($site)
            ->published()
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale)->where('slug', $slug))
            ->with(['translations', 'blocks', 'seo.ogImage', 'featuredMedia', 'parent'])
            ->first();

        if (! $page) {
            // A missing translation falls back to any published translation of
            // the same page, so a locale switch never lands on a dead URL.
            $page = Page::forSite($site)
                ->published()
                ->whereHas('translations', fn ($query) => $query->where('slug', $slug))
                ->with(['translations', 'blocks', 'seo.ogImage', 'featuredMedia', 'parent'])
                ->first();
        }

        if (! $page) {
            return response()->json([
                'message' => 'This page could not be found in the requested language.',
                'code' => 'page_not_found',
            ], 404);
        }

        return response()->json(['data' => $this->presenter->page($page, $locale)]);
    }

    public function preview(Request $request, string $token): JsonResponse
    {
        $preview = PagePreviewToken::where('token', $token)->with('page')->first();

        abort_unless($preview && $preview->isValid(), 404, 'This preview link is invalid or has expired.');

        $page = $preview->page;
        abort_unless($page, 404);

        $site = Site::find($preview->site_id);
        abort_unless($site, 404);

        $this->context->setSite($site);
        $locale = $this->context->normalizeLocale($preview->locale);
        $this->context->setLocale($locale);

        $page->load(['translations', 'blocks', 'seo.ogImage', 'featuredMedia', 'parent']);

        return response()->json(['data' => $this->presenter->page($page, $locale, true)]);
    }

    public function resolve(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();
        $path = '/'.ltrim((string) $request->query('path', ''), '/');
        $routeLocale = config('meridian.locales')[$locale]['route'] ?? strtolower($locale);
        $slug = trim(str_replace('/'.$routeLocale.'/', '', $path), '/');

        $page = Page::forSite($site)->published()
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('slug', $slug))
            ->first();

        if ($page) {
            return response()->json(['data' => ['type' => 'page', 'slug' => $slug]]);
        }

        $redirect = Redirect::forSite($site)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('locale', $locale)->orWhereNull('locale'))
            ->where('from_path', $path)
            ->first();

        if ($redirect) {
            $redirect->increment('hits');

            return response()->json(['data' => [
                'type' => 'redirect',
                'to' => $redirect->to_path,
                'status' => $redirect->status_code,
            ]]);
        }

        return response()->json(['data' => null], 404);
    }
}
