<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\AnalyticsService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsAdminController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected AnalyticsService $analytics,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $from = $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay();

        $data = $this->analytics->dashboard($site, $from, $to);
        $data['activity'] = $this->analytics->contentActivity($site, $from, $to);

        return response()->json(['data' => $data]);
    }
}
