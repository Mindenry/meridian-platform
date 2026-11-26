<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\TeamMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamAdminController extends Controller
{
    public function __construct(protected SiteContext $context) {}

    public function index(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();

        return response()->json(['data' => TeamMember::forSite($site)->with('photo')->orderBy('sort')->get()->map(fn (TeamMember $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'role' => $member->role,
            'department' => $member->department,
            'bio' => $member->bio,
            'photo' => $member->photo?->toPublicArray('en'),
            'is_leadership' => $member->is_leadership,
            'sort' => $member->sort,
            'social' => $member->social,
        ])->all()]);
    }

    public function store(Request $request): JsonResponse
    {
        $site = $this->context->siteOrFail();
        abort_unless($request->user()->hasPermission('users.manage', $site->id), 403);
        $data = $this->validated($request);

        $member = TeamMember::create($data + ['site_id' => $site->id]);

        return response()->json(['data' => ['id' => $member->id], 'message' => 'Team member added.'], 201);
    }

    public function update(Request $request, TeamMember $teamMember): JsonResponse
    {
        abort_unless($teamMember->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('users.manage', $teamMember->site_id), 403);
        $teamMember->update($this->validated($request, false));

        return response()->json(['message' => 'Team member updated.']);
    }

    public function destroy(Request $request, TeamMember $teamMember): JsonResponse
    {
        abort_unless($teamMember->site_id === $this->context->id(), 404);
        abort_unless($request->user()->hasPermission('users.manage', $teamMember->site_id), 403);
        $teamMember->delete();

        return response()->json(['message' => 'Team member removed.']);
    }

    protected function validated(Request $request, bool $creating = true): array
    {
        $data = $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'role' => [$creating ? 'required' : 'sometimes', 'array'],
            'role.en' => ['nullable', 'string', 'max:160'],
            'role.th' => ['nullable', 'string', 'max:160'],
            'role.zh-CN' => ['nullable', 'string', 'max:160'],
            'department' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'array'],
            'bio.en' => ['nullable', 'string', 'max:1200'],
            'bio.th' => ['nullable', 'string', 'max:1200'],
            'bio.zh-CN' => ['nullable', 'string', 'max:1200'],
            'photo_media_id' => ['nullable', 'integer'],
            'is_leadership' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
            'social' => ['nullable', 'array'],
        ]);

        if (! empty($data['photo_media_id'])) {
            abort_unless(MediaAsset::forSite($this->context->siteOrFail())->whereKey($data['photo_media_id'])->exists(), 422, 'The photo is invalid.');
        }

        return $data;
    }
}
