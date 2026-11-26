<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentsAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json(['data' => Document::forSite($site)->with('media')->orderByDesc('year')->orderBy('sort')->get()->map(fn (Document $document) => [
            'id' => $document->id,
            'title' => $document->title,
            'description' => $document->description,
            'category' => $document->category,
            'year' => $document->year,
            'file_url' => $document->media?->url,
            'file_name' => $document->media?->original_name,
            'file_size' => $document->media?->size,
            'is_illustrative' => $document->is_illustrative,
            'published_at' => $document->published_at?->toIso8601String(),
        ])->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('documents.manage', $site->id), 403);
        $data = $this->validated($request);

        if (! empty($data['media_id'])) {
            abort_unless(MediaAsset::forSite($site)->whereKey($data['media_id'])->exists(), 422, 'The selected file is invalid.');
        }

        $document = Document::create($data + ['site_id' => $site->id, 'is_illustrative' => true]);

        return response()->json(['data' => ['id' => $document->id], 'message' => 'Document added.'], 201);
    }

    public function update(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('documents.manage', $document->site_id), 403);
        $document->update($this->validated($request, false));

        return response()->json(['message' => 'Document updated.']);
    }

    public function destroy(Request $request, Document $document): JsonResponse
    {
        abort_unless($document->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('documents.manage', $document->site_id), 403);
        $document->delete();

        return response()->json(['message' => 'Document removed.']);
    }

    protected function validated(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'array'],
            'title.en' => [$creating ? 'required' : 'sometimes', 'string', 'max:200'],
            'title.th' => ['nullable', 'string', 'max:200'],
            'title.zh-CN' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'array'],
            'category' => [$creating ? 'required' : 'sometimes', 'in:annual_report,financial,governance,corporate'],
            'year' => [$creating ? 'required' : 'sometimes', 'integer', 'min:2000', 'max:2100'],
            'media_id' => ['nullable', 'integer'],
            'published_at' => ['nullable', 'date'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
