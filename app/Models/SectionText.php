<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Anasayfa bölümünün başlık yazıları (key ile: "steps"…): üst etiket, başlık, kırmızı kısım, açıklama.
 */
class SectionText extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'eyebrow', 'title', 'title_highlight', 'description'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['eyebrow', 'title', 'title_highlight', 'description'];

    public function getLocale(): string
    {
        return 'en';
    }

    public static function for(string $key): self
    {
        return static::query()->firstOrCreate(['key' => $key]);
    }
}
