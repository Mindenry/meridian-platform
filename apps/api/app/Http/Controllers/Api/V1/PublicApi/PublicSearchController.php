<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\SearchService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicSearchController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected SearchService $search,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        return response()->json([
            'data' => $this->search->search(
                $this->context->siteOrFail(),
                $this->context->locale(),
                (string) ($validated['q'] ?? ''),
                (int) ($validated['limit'] ?? 20),
            ),
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->search->suggestions(
                $this->context->siteOrFail(),
                $this->context->locale(),
                $query,
            ),
        ]);
    }
}
