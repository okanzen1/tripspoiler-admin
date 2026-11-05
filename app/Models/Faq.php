<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Sıkça sorulan soru: bir aktivitenin ya da blog yazısının sayfasında; ikisi de boşsa genel SSS.
 */
class Faq extends Model
{
    use HasTranslations;

    protected $fillable = ['activity_id', 'blog_id', 'city_id', 'page_key', 'question', 'answer', 'sort_order', 'active'];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['question', 'answer'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'page_key', 'key');
    }

    public function scopeGeneral(Builder $query): void
    {
        $query->whereNull('activity_id')->whereNull('blog_id')->whereNull('city_id')->whereNull('page_key');
    }

    /**
     * @return list<string>
     */
    public function otherLocales(): array
    {
        return array_values(array_diff($this->getTranslatedLocales('question'), ['en']));
    }
}
