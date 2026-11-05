<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Sitenin en üstündeki kayan şeritte dönen duyuru. Bağlantı verilirse tıklanabilir.
 */
class Announcement extends Model
{
    use HasTranslations;

    protected $fillable = ['text', 'link', 'active', 'sort_order'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['text'];

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
        return array_values(array_diff($this->getTranslatedLocales('text'), ['en']));
    }
}
