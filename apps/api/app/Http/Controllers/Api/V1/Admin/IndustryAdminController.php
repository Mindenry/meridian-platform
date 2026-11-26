<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\IndustryTranslation;
use App\Models\Solution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IndustryAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $items = Industry::forSite($site)->with(['translations', 'featuredMedia'])->orderBy('sort')->get();

        return response()->json(['data' => $items->map(fn (Industry $industry) => [
            'id' => $industry->id,
            'key' => $industry->key,
            'icon' => $industry->icon,
            'status' => $industry->status,
            'title' => $industry->translations->first()?->title,
            'slug' => $industry->translations->first()?->slug,
            'image' => $industry->featuredMedia?->url,
            'locales' => $industry->translations->pluck('locale')->values()->all(),
        ])->all()]);
    }

    public function show(Request $request, Industry $industry): JsonResponse
    {
        abort_unless($industry->site_id === $this->context->id(), 404);
        $locale = $this->context->normalizeLocale($request->query('locale'));
        $translation = $industry->translations()->where('locale', $locale)->first() ?? $industry->translations()->first();

        return response()->json(['data' => [
            'id' => $industry->id,
            'key' => $industry->key,
            'icon' => $industry->icon,
            'status' => $industry->status,
            'locale' => $translation?->locale,
            'title' => $translation?->title,
            'slug' => $translation?->slug,
            'tagline' => $translation?->tagline,
            'overview' => $translation?->overview,
            'challenges' => $translation?->challenges ?? [],
            'capabilities' => $translation?->capabilities ?? [],
            'stats' => $translation?->stats ?? [],
            'featured_media' => $industry->featuredMedia?->toPublicArray($locale),
            'solution_ids' => $industry->solutions->pluck('id')->values()->all(),
            'solutions' => Solution::forSite($industry->site)->with('translations')->orderBy('sort')->get()->map(fn ($solution) => [
                'id' => $solution->id, 'key' => $solution->key, 'title' => $solution->translations->first()?->title,
            ])->all(),
            'translations' => $industry->translations->map(fn ($t) => ['locale' => $t->locale, 'title' => $t->title, 'slug' => $t->slug])->values()->all(),
        ]]);
    }

    public function update(Request $request, Industry $industry): JsonResponse
    {
        abort_unless($industry->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('industries.manage', $industry->site_id), 403);

        $data = $request->validate([
            'locale' => ['required', 'in:th,en,zh-CN'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:500'],
            'overview' => ['required', 'string', 'max:10000'],
            'challenges' => ['nullable', 'array'],
            'capabilities' => ['nullable', 'array'],
            'stats' => ['nullable', 'array'],
            'status' => ['nullable', 'in:published,draft'],
            'icon' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'featured_media_id' => ['nullable', 'integer'],
            'solution_ids' => ['nullable', 'array'],
        ]);

        $slug = $this->slug($data['slug'] ?? $data['title']);
        $this->assertSlug($industry->site_id, $data['locale'], $slug, $industry->id);

        $industry->update([
            'status' => $data['status'] ?? $industry->status,
            'icon' => $data['icon'] ?? $industry->icon,
            'sort' => $data['sort'] ?? $industry->sort,
            'featured_media_id' => $data['featured_media_id'] ?? $industry->featured_media_id,
        ]);

        $industry->translations()->updateOrCreate(
            ['locale' => $data['locale']],
            [
                'site_id' => $industry->site_id,
                'title' => $data['title'],
                'slug' => $slug,
                'tagline' => $data['tagline'] ?? null,
                'overview' => $data['overview'],
                'challenges' => $data['challenges'] ?? [],
                'capabilities' => $data['capabilities'] ?? [],
                'stats' => $data['stats'] ?? [],
            ]
        );

        if (array_key_exists('solution_ids', $data)) {
            $industry->solutions()->sync($data['solution_ids'] ?? []);
        }

        return response()->json(['message' => 'Industry saved.']);
    }

    protected function slug(string $value): string
    {
        $slug = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', trim($value)) ?? '';
        $slug = trim(preg_replace('/-+/u', '-', preg_replace('/[\s_]+/u', '-', $slug) ?? '') ?? '', '-');

        return mb_strtolower($slug !== '' ? $slug : 'industry-'.Str::lower(Str::random(6)));
    }

    protected function assertSlug(int $siteId, string $locale, string $slug, ?int $exceptId = null): void
    {
        $query = IndustryTranslation::where('site_id', $siteId)->where('locale', $locale)->where('slug', $slug);
        if ($exceptId) {
            $query->where('industry_id', '!=', $exceptId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'That slug is already used by another industry.']);
        }
    }
}
