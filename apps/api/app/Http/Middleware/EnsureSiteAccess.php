<?php

namespace App\Http\Middleware;

use App\Cms\SiteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteAccess
{
    public function __construct(protected SiteContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Authentication is required.');

        $site = $this->context->siteOrFail();

        abort_unless($user->belongsToSite($site), 403, 'You do not have access to this website.');

        return $next($request);
    }
}
