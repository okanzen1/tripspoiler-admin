<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Şehir rehberi alanı (ör. "Yeme içme"). Her şehir bu alanlara kendi başlığını ve yazısını yazar.
 */
class CityGuideTopic extends Model
{
    use HasTranslations;

    protected $fillable = ['key', 'name', 'icon', 'example', 'sort_order', 'active'];

    /** Panel İngilizce (en) düzenler, diğer diller Çeviriler'den */
    public array $translatable = ['name'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CityGuideSection::class, 'topic_id');
    }
}
