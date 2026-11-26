<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\MediaService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\MediaCollection;
use App\Models\MediaFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected MediaService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $perPage = min(100, max(12, (int) $request->query('per_page', 36)));

        $query = MediaAsset::forSite($site)
            ->with(['folder', 'uploader'])
            ->when($request->query('q'), fn ($q, $search) => $q->where(fn ($inner) => $inner
                ->where('original_name', 'like', '%'.$search.'%')
                ->orWhere('alt', 'like', '%'.$search.'%')))
            ->when($request->query('type') === 'image', fn ($q) => $q->where('mime_type', 'like', 'image/%'))
            ->when($request->query('type') === 'document', fn ($q) => $q->where('mime_type', 'application/pdf'))
            ->when($request->query('folder_id'), fn ($q, $folder) => $q->where('folder_id', $folder));

        $sort = $request->query('sort', 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('original_name'),
            'size' => $query->orderByDesc('size'),
            default => $query->orderByDesc('created_at'),
        };

        $paginator = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => collect($paginator->items())->map(fn (MediaAsset $asset) => $this->assetPayload($asset))->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
            ],
            'folders' => MediaFolder::forSite($site)->orderBy('path')->get()->map(fn ($folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'parent_id' => $folder->parent_id,
                'count' => $folder->assets()->count(),
            ])->all(),
            'collections' => MediaCollection::forSite($site)->withCount('assets')->get()->map(fn ($collection) => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'assets_count' => $collection->assets_count,
            ])->all(),
            'storage' => [
                'bytes' => (int) MediaAsset::forSite($site)->sum('size'),
                'count' => MediaAsset::forSite($site)->count(),
                'quota_bytes' => (int) data_get($site->settings, 'media_quota_bytes', 5 * 1024 * 1024 * 1024),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'max:10240'],
            'folder_id' => ['nullable', 'integer'],
            'alt' => ['nullable', 'array'],
            'caption' => ['nullable', 'array'],
            'credit' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $assets = [];
        $duplicates = 0;

        foreach ($request->file('files') as $file) {
            $result = $this->media->upload($site, $request->user(), $file, $request->only([
                'folder_id', 'alt', 'caption', 'credit', 'license', 'source_url',
            ]));

            $duplicates += $result['duplicate'] ? 1 : 0;
            $assets[] = $this->assetPayload($result['asset']);
        }

        return response()->json([
            'data' => $assets,
            'duplicates' => $duplicates,
            'message' => count($assets).' file(s) uploaded.',
        ], 201);
    }

    public function show(Request $request, MediaAsset $asset): JsonResponse
    {
        $this->ensureSite($asset);

        return response()->json([
            'data' => array_merge($this->assetPayload($asset), [
                'usage' => $this->media->usageReport($asset),
            ]),
        ]);
    }

    public function update(Request $request, MediaAsset $asset): JsonResponse
    {
        $this->ensureSite($asset);
        abort_unless($request->user()->hasPermission('media.edit', $asset->site_id), 403);

        $data = $request->validate([
            'alt' => ['nullable', 'array'],
            'caption' => ['nullable', 'array'],
            'credit' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'license' => ['nullable', 'string', 'max:120'],
            'source_url' => ['nullable', 'url', 'max:1000'],
            'focal_x' => ['nullable', 'numeric', 'between:0,1'],
            'focal_y' => ['nullable', 'numeric', 'between:0,1'],
            'folder_id' => ['nullable', 'integer'],
        ]);

        $asset = $this->media->update($asset, $request->user(), $data);

        return response()->json(['data' => $this->assetPayload($asset), 'message' => 'Media updated.']);
    }

    public function usage(Request $request, MediaAsset $asset): JsonResponse
    {
        $this->ensureSite($asset);

        return response()->json(['data' => $this->media->usageReport($asset)]);
    }

    public function destroy(Request $request, MediaAsset $asset): JsonResponse
    {
        $this->ensureSite($asset);
        abort_unless($request->user()->hasPermission('media.delete', $asset->site_id), 403);

        $usage = $this->media->usageReport($asset);

        if ($usage !== [] && ! $request->boolean('force')) {
            return response()->json([
                'message' => 'This asset is referenced by published content. Resolve those references or confirm a forced deletion.',
                'code' => 'media_in_use',
                'usage' => $usage,
            ], 409);
        }

        $this->media->delete($asset, $request->user());

        return response()->json(['message' => 'Media deleted.']);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parent = $data['parent_id'] ? MediaFolder::forSite($site)->whereKey($data['parent_id'])->first() : null;
        $path = $parent ? $parent->path.'/'.$this->slug($data['name']) : $this->slug($data['name']);

        $folder = MediaFolder::firstOrCreate([
            'site_id' => $site->id,
            'path' => $path,
        ], [
            'parent_id' => $parent?->id,
            'name' => $data['name'],
        ]);

        return response()->json(['data' => ['id' => $folder->id, 'name' => $folder->name, 'path' => $folder->path]], 201);
    }

    protected function assetPayload(MediaAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'url' => $asset->url,
            'original_name' => $asset->original_name,
            'filename' => $asset->filename,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'size' => $asset->size,
            'width' => $asset->width,
            'height' => $asset->height,
            'alt' => $asset->alt,
            'caption' => $asset->caption,
            'credit' => $asset->credit,
            'license' => $asset->license,
            'source_url' => $asset->source_url,
            'focal_x' => $asset->focal_x,
            'focal_y' => $asset->focal_y,
            'variants' => $asset->variantUrls(),
            'usage_count' => $asset->usage_count,
            'folder' => $asset->folder ? ['id' => $asset->folder->id, 'name' => $asset->folder->name] : null,
            'uploaded_by' => $asset->uploader?->name,
            'created_at' => $asset->created_at?->toIso8601String(),
        ];
    }

    protected function ensureSite(MediaAsset $asset): void
    {
        abort_unless($asset->site_id === $this->context->id(), 404);
    }

    protected function slug(string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $value) ?: 'folder');
    }
}
