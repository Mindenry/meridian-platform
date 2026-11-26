<?php

namespace App\Cms;

use App\Models\Site;

class SiteContext
{
    protected ?Site $site = null;

    protected ?string $locale = null;

    public function setSite(Site $site): void
    {
        $this->site = $site;
    }

    public function site(): ?Site
    {
        return $this->site;
    }

    public function siteOrFail(): Site
    {
        if (! $this->site) {
            abort(404, 'Website context could not be resolved.');
        }

        return $this->site;
    }

    public function id(): ?int
    {
        return $this->site?->id;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function locale(): string
    {
        return $this->locale
            ?? $this->site?->default_locale
            ?? config('meridian.default_locale');
    }

    public function fallbackLocale(): string
    {
        return config('meridian.fallback_locale');
    }

    /**
     * Resolve a URL locale segment (zh-cn) to a stored locale (zh-CN).
     */
    public function normalizeLocale(?string $locale): string
    {
        if (! $locale) {
            return $this->site?->default_locale ?? config('meridian.default_locale');
        }

        foreach (array_keys(config('meridian.locales')) as $candidate) {
            if (strcasecmp($candidate, $locale) === 0) {
                return $candidate;
            }
        }

        return $this->site?->default_locale ?? config('meridian.default_locale');
    }

    public function isEnabledLocale(string $locale): bool
    {
        return in_array($locale, $this->site?->enabled_locales ?? [], true);
    }
}
