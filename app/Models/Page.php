<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Sitenin sabit sayfası (anasayfa, iletişim…). Şimdilik SEO başlığı ve açıklaması tutulur.
 */
class Page extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'name', 'path', 'meta_title', 'meta_description', 'sort_order'];

    public array $translatable = ['meta_title', 'meta_description'];

    public function getLocale(): string
    {
        return 'en';
    }

    /** SEO bu sayfada değil, her aktivitenin / blog yazısının kendi formunda (Aktivite detayı, Blog yazısı) */
    public function hasOwnSeo(): bool
    {
        return !in_array($this->key, ['activity', 'post'], true);
    }

    public function isSeoComplete(): bool
    {
        return !$this->hasOwnSeo() || (filled($this->meta_title) && filled($this->meta_description));
    }

    /**
     * Sitedeki adresi ("Sayfayı aç"). Aktivite detayı için ilk yayındaki aktivite.
     */
    public function siteUrl(): string
    {
        $base = rtrim(config('site.url'), '/') . '/en';
        if ($this->key === 'activity') {
            $slug = Activity::where('active', true)->orderBy('sort_order')->orderBy('id')->value('slug');

            return $base . '/activities' . ($slug ? '/' . $slug : '');
        }
        if ($this->key === 'post') {
            $slug = \App\Models\Blog::where('active', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderByDesc('published_at')->value('slug');

            return $base . '/blog' . ($slug ? '/' . $slug : '');
        }

        return $base . ($this->path === '/' ? '' : $this->path);
    }
}
