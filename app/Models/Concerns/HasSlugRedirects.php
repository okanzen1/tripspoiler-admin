<?php

namespace App\Models\Concerns;

use App\Models\Language;
use App\Models\Redirect;
use App\Support\SitePath;

/**
 * Slug değişince eski adres her dilde yeni adrese yönlendirilir (Search Console'da 404 olmasın).
 * Modelde SITE_PATH sabiti config/site.php'deki kalıbın adıdır (activity, blog, city).
 */
trait HasSlugRedirects
{
    protected static function bootHasSlugRedirects(): void
    {
        static::updated(function (self $model) {
            if (!$model->wasChanged('slug') || blank($old = $model->getOriginal('slug'))) {
                return;
            }

            foreach (Language::active()->pluck('code') as $locale) {
                Redirect::point(SitePath::for(static::SITE_PATH, $locale, $old), SitePath::for(static::SITE_PATH, $locale, $model->slug));
            }
        });
    }
}
