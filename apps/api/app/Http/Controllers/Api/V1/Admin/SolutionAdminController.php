<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\SeoMetadata;
use App\Models\Solution;
use App\Models\SolutionTranslation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SolutionAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $items = Solution::forSite($site)->with(['translations', 'featuredMedia'])->orderBy('sort')->get();

        return response()->json(['data' => $items->map(fn (Solution $solution) => [
            'id' => $solution->id,
            'key' => $solution->key,
            'icon' => $solution->icon,
            'status' => $solution->status,
            'title' => $solution->translations->first()?->title,
            'slug' => $solution->translations->first()?->slug,
            'image' => $solution->featuredMedia?->url,
            'locales' => $solution->translations->pluck('locale')->values()->all(),
        ])->all(), 'industries' => Industry::forSite($site)->with('translations')->orderBy('sort')->get()->map(fn ($industry) => [
            'id' => $industry->id, 'key' => $industry->key, 'title' => $industry->translations->first()?->title,
        ])->all()]);
    }

    public function show(Request $request, Solution $solution): JsonResponse
    {
        abort_unless($solution->site_id === $this->context->id(), 404);
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $translation = $solution->translations()->where('locale', $locale)->first() ?? $solution->translations()->first();

        return response()->json(['data' => [
            'id' => $solution->id,
            'key' => $solution->key,
            'icon' => $solution->icon,
            'status' => $solution->status,
            'sort' => $solution->sort,
            'locale' => $translation?->locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'tagline' => $translation?->tagline,
            'overview' => $translation?->overview,
            'problems' => $translation?->problems ?? [],
            'approach' => $translation?->approach ?? [],
            'capabilities' => $translation?->capabilities ?? [],
            'benefits' => $translation?->benefits ?? [],
            'featured_media' => $solution->featuredMedia?->toPublicArray($locale),
            'industry_ids' => $solution->industries->pluck('id')->values()->all(),
            'translations' => $solution->translations->map(fn ($t) => ['locale' => $t->locale, 'title' => $t->title, 'slug' => $t->slug])->values()->all(),
        ]]);
    }

    public function update(Request $request, Solution $solution): JsonResponse
    {
        abort_unless($solution->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('solutions.manage', $solution->site_id), 403);

        $data = $request->validate([
            'locale' => ['required', 'in:th,en,zh-CN'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'overview' => ['required', 'string', 'max:10000'],
            'problems' => ['nullable', 'array'],
            'approach' => ['nullable', 'array'],
            'capabilities' => ['nullable', 'array'],
            'benefits' => ['nullable', 'array'],
            'status' => ['nullable', 'in:published,draft'],
            'icon' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'featured_media_id' => ['nullable', 'integer'],
            'industry_ids' => ['nullable', 'array'],
        ]);

        $slug = $this->slug($data['slug'] ?? $data['title']);
        $this->assertSlug($solution->site_id, $data['locale'], $slug, $solution->id);

        $solution->update([
            'status' => $data['status'] ?? $solution->status,
            'icon' => $data['icon'] ?? $solution->icon,
            'sort' => $data['sort'] ?? $solution->sort,
            'featured_media_id' => $data['featured_media_id'] ?? $solution->featured_media_id,
        ]);

        $solution->translations()->updateOrCreate(
            ['locale' => $data['locale']],
            [
                'site_id' => $solution->site_id,
                'title' => $data['title'],
                'slug' => $slug,
                'tagline' => $data['tagline'] ?? null,
                'overview' => $data['overview'],
                'problems' => $data['problems'] ?? [],
                'approach' => $data['approach'] ?? [],
                'capabilities' => $data['capabilities'] ?? [],
                'benefits' => $data['benefits'] ?? [],
            ]
        );

        if (array_key_exists('industry_ids', $data)) {
            $solution->industries()->sync($data['industry_ids'] ?? []);
        }

        if (array_key_exists('seo', $data)) {
            SeoMetadata::updateOrCreate(
                ['seoable_type' => 'solution', 'seoable_id' => $solution->id, 'locale' => $data['locale']],
                ['site_id' => $solution->site_id] + $data['seo']
            );
        }

        return response()->json(['message' => 'Solution saved.']);
    }

    protected function slug(string $value): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', trim($value)) ?? '';
        $slug = trim(preg_replace('/-+/u', '-', preg_replace('/[\s_]+/u', '-', $slug) ?? '') ?? '', '-');

        return mb_strtolower($slug !== '' ? $slug : 'solution-'.Str::lower(Str::random(6)));
    }

    protected function assertSlug(int $siteId, string $locale, string $slug, ?int $exceptId = null): void
    {
        $query = SolutionTranslation::where('site_id', $siteId)->where('locale', $locale)->where('slug', $slug);
        if ($exceptId) {
            $query->where('solution_id', '!=', $exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'That slug is already used by another solution.']);
        }
    }
}
