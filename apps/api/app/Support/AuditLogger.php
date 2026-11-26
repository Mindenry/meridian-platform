<?php

namespace App\Support;

use App\Cms\SiteContext;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(protected SiteContext $context) {}

    public function log(string $action, string $description, array $options = []): AuditLog
    {
        $request = app()->bound('request') ? app(Request::class) : null;

        return AuditLog::create([
            'site_id' => $options['site_id'] ?? $this->context->id(),
            'user_id' => $options['user_id'] ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'subject_type' => $options['subject_type'] ?? null,
            'subject_id' => $options['subject_id'] ?? null,
            'meta' => $options['meta'] ?? null,
            'ip_address' => $options['ip'] ?? $request?->ip(),
            'user_agent' => mb_substr((string) ($options['user_agent'] ?? $request?->userAgent()), 0, 500),
            'created_at' => now(),
        ]);
    }
}
