<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Bir şehrin bir rehber alanına yazdığı içerik: başlık, editör metni, isteğe bağlı kapak resmi.
 * Sitede görünmesi için hem bu kayıt hem alan (topic) açık olmalı ve başlık/metin dolu olmalı.
 */
class CityGuideSection extends Model
{
    use HasTranslations;

    protected $fillable = ['city_id', 'topic_id', 'title', 'content', 'image_path', 'active', 'sort_order'];

    public array $translatable = ['title', 'content'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(CityGuideTopic::class, 'topic_id');
    }

    /** Yazılmış mı (İngilizce başlık ve metin dolu) */
    public function isWritten(): bool
    {
        return filled($this->getTranslation('title', 'en', false)) && filled(trim(strip_tags((string) $this->getTranslation('content', 'en', false))));
    }
}
