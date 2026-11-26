<?php

namespace App\Http\Middleware;

use App\Cms\SiteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function __construct(protected SiteContext $context) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Authentication is required.');

        abort_unless(
            $user->hasPermission($permission, $this->context->site()),
            403,
            "Your role does not include the [{$permission}] permission."
        );

        return $next($request);
    }
}
