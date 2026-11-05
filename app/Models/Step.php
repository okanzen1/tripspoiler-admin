<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Anasayfadaki "Nasıl çalışır" adımı: ikon (App\Support\FeatureIcons), başlık, yazı, bağlantı yazısı ve adresi.
 */
class Step extends Model
{
    use HasTranslations;

    protected $fillable = ['icon', 'title', 'text', 'cta', 'link', 'active', 'sort_order'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['title', 'text', 'cta'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
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
