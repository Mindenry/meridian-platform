<?php

namespace App\Http\Controllers\Api\V1\PublicApi;

use App\Cms\MediaService;
use App\Cms\SiteContext;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use App\Notifications\ContentWorkflowNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PublicJobApplicationController extends Controller
{
    public function __construct(
        protected SiteContext $context,
        protected MediaService $media,
    ) {}

    public function store(Request $request, string $slug): JsonResponse
    {
        $site = $this->context->siteOrFail();
        $locale = $this->context->locale();

        $job = JobPosting::forSite($site)->open()
            ->whereHas('translations', fn ($query) => $query->where('locale', $locale)->where('slug', $slug))
            ->first();

        abort_unless($job, 404, 'This role is no longer open.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cover_letter' => ['nullable', 'string', 'max:6000'],
            'portfolio_url' => ['nullable', 'url', 'max:1000'],
            'resume' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string', 'max:200'],
        ]);

        if (trim((string) ($validated['website'] ?? '')) !== '') {
            return response()->json(['message' => 'Thank you. Your application has been received.'], 201);
        }

        $upload = $this->media->upload($site, null, $request->file('resume'), [
            'alt' => ['en' => 'Candidate résumé', 'th' => 'เรซูเม่ผู้สมัคร'],
            'credit' => 'Candidate submitted',
        ]);

        $application = JobApplication::create([
            'site_id' => $site->id,
            'job_posting_id' => $job->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'resume_media_id' => $upload['asset']->id,
            'cover_letter' => $validated['cover_letter'] ?? null,
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'status' => 'new',
            'consent_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        $recipients = User::where('is_active', true)
            ->where(function ($query) use ($site) {
                $query->whereHas('roles', fn ($role) => $role->where('role_user.site_id', $site->id)
                    ->whereHas('permissions', fn ($permission) => $permission->where('slug', 'applications.view')))
                    ->orWhereHas('roles', fn ($role) => $role->where('slug', 'super_admin'));
            })
            ->get();

        Notification::send($recipients, new ContentWorkflowNotification(
            'New job application',
            "{$application->name} applied for the role.",
            rtrim((string) config('meridian.studio_url'), '/').'/careers/applications/'.$application->id,
            'career',
        ));

        return response()->json([
            'message' => 'Thank you. Your application has been received and the careers team will review it.',
            'reference' => 'APP-'.str_pad((string) $application->id, 6, '0', STR_PAD_LEFT),
        ], 201);
    }
}
