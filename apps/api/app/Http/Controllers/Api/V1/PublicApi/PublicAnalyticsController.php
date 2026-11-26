<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\AnalyticsService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAnalyticsController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected AnalyticsService $analytics,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['nullable', 'string', 'in:page_view,search,form_submit,cta_click,article_read'],
            'path' => ['required', 'string', 'max:1000'],
            'locale' => ['nullable', 'string', 'max:10'],
            'referrer' => ['nullable', 'string', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'consent' => ['required', 'boolean'],
            'meta' => ['nullable', 'array'],
            'meta.query' => ['nullable', 'string', 'max:200'],
        ]);

        if (! $validated['consent']) {
            return response()->json(['message' => 'Analytics consent not provided; event ignored.'], 202);
        }

        $this->analytics->record(
            $this->context->siteOrFail(),
            $validated,
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['message' => 'Recorded.'], 202);
    }
}
