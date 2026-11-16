<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Dillere göre adres (slug): /de/blog/athen-reisefuehrer, /tr/blog/atina-gezi-rehberi…
 * "slugs" sütunu = {"tr": "...", "de": "..."}; yalnızca Latin harfle yazılabilen diller. Japonca, Çince, Korece ve
 * Arapçada adres İngilizce kalır (bozuk / okunmaz adres olmasın); Rusça Latin harflere çevrilir (afiny-…).
 * Bir kez yazılan adres değişmez (paylaşılan linkler kırılmasın); başlık çevirisi gelince eksik diller dolar.
 * Modelde SLUG_SOURCE sabiti: adresin üretildiği çevrilen alan (title / name).
 */
trait HasLocalizedSlugs
{
    public const SLUG_LOCALES = ['tr', 'de', 'fr', 'es', 'it', 'pt', 'ru'];

    protected static function bootHasLocalizedSlugs(): void
    {
        static::saving(function (self $model): void {
            $model->fillLocalizedSlugs(); // dönüş değeri verilmez: false kaydı iptal ederdi
        });
    }

    public function initializeHasLocalizedSlugs(): void
    {
        $this->mergeCasts(['slugs' => 'array']);
    }

    /** Eksik dillerin adresini başlığın o dildeki çevirisinden üretir; true = bir şey eklendi */
    public function fillLocalizedSlugs(): bool
    {
        $slugs = $this->slugs ?? [];
        $added = false;

        foreach (self::SLUG_LOCALES as $locale) {
            if (filled($slugs[$locale] ?? null)) {
                continue;
            }
            $title = $this->getTranslation(static::SLUG_SOURCE, $locale, false);
            if (blank($title) || $title === $this->getTranslation(static::SLUG_SOURCE, 'en', false)) {
                continue; // çevrilmemiş: İngilizce adres kullanılır
            }
            $base = Str::slug(Str::limit(strip_tags($title), 90, ''), '-', $locale);
            $base = trim(Str::limit($base, 80, ''), '-');
            if (strlen($base) < 3 || !preg_match('/^[a-z0-9-]+$/', $base)) {
                continue;
            }

            $candidate = $base;
            for ($i = 2; $this->localizedSlugTaken($locale, $candidate); $i++) {
                $candidate = $base . '-' . $i;
            }
            $slugs[$locale] = $candidate;
            $added = true;
        }

        if ($added) {
            $this->slugs = $slugs;
        }

        return $added;
    }

    private function localizedSlugTaken(string $locale, string $slug): bool
    {
        return static::query()
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->where(fn ($q) => $q->where("slugs->{$locale}", $slug)->orWhere('slug', $slug))
            ->exists();
    }
}
