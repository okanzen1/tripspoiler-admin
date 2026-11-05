<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Şehir sayfasının üstündeki genel başlık ve açıklama (şehir başına bir kayıt).
 */
class CityGuide extends Model
{
    use HasTranslations;

    protected $fillable = ['city_id', 'title', 'description', 'hero_cards'];

    public array $translatable = ['title', 'description'];

    public function getLocale(): string
    {
        return 'en';
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
