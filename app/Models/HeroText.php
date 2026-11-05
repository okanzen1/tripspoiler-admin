<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Anasayfa hero'sundaki yazılar (tek kayıt): üst etiket, üç satırlık başlık, kırmızı vurgu ve açıklama.
 * Panel İngilizce düzenler; diğer diller Çeviriler'den.
 */
class HeroText extends Model
{
    use HasTranslations;

    protected $fillable = ['eyebrow', 'title_1', 'title_2', 'title_3', 'title_highlight', 'description'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['eyebrow', 'title_1', 'title_2', 'title_3', 'title_highlight', 'description'];

    public function getLocale(): string
    {
        return 'en';
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
