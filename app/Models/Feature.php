<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Anasayfadaki özellik kartı: ikon (App\Support\FeatureIcons), başlık ve kısa yazı.
 */
class Feature extends Model
{
    use HasTranslations;

    protected $fillable = ['icon', 'icon_path', 'title', 'text', 'active', 'sort_order'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['title', 'text'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Kart silinince yüklenen ikonu da silinir
        static::deleted(fn (self $feature) => app(\App\Services\ImageUploader::class)->delete($feature->icon_path));
    }

    /**
     * Kartın ikonu: yüklenen özel ikon (şekli maske olarak, renk currentColor) ya da hazır ikon.
     */
    public function iconHtml(string $class = 'size-6'): string
    {
        if ($this->icon_path) {
            $url = e(\App\Services\ImageUploader::url($this->icon_path));

            return '<span class="inline-block ' . e($class) . '" aria-hidden="true" style="background-color: currentColor; -webkit-mask: url(\'' . $url . '\') center / contain no-repeat; mask: url(\'' . $url . '\') center / contain no-repeat"></span>';
        }

        return \App\Support\FeatureIcons::svg($this->icon, $class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @return list<string>
     */
    public function otherLocales(): array
    {
        return array_values(array_diff($this->getTranslatedLocales('title'), ['en']));
    }
}
