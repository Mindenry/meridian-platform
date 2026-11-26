<?php

namespace App\Http\Middleware;

use App\Cms\SiteContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(protected SiteContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->query('locale') ?? $request->header('X-Locale');
        $locale = $this->context->normalizeLocale($requested);

        if (! $this->context->isEnabledLocale($locale)) {
            $locale = $this->context->siteOrFail()->default_locale;
        }

        $this->context->setLocale($locale);
        app()->setLocale($locale);

        return $next($request);
    }
}
