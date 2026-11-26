<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasLocalizedTranslations
{
    abstract public function translations(): HasMany;

    public function translationFor(string $locale, ?string $fallback = null): mixed
    {
        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        $translation = $translations->firstWhere('locale', $locale);

        if (! $translation && $fallback) {
            $translation = $translations->firstWhere('locale', $fallback);
        }

        return $translation;
    }

    public function availableLocales(): Collection
    {
        $translations = $this->relationLoaded('translations')
            ? $this->translations
            : $this->translations()->get();

        return $translations->pluck('locale')->values();
    }
}
