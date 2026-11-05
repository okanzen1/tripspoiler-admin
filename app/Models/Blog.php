<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRedirects;
use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    use HasSlugRedirects, HasTranslations;

    /** Front'taki adres kalıbı (config/site.php) */
    public const SITE_PATH = 'blog';

    protected $fillable = [
        'city_id', 'title', 'slug', 'excerpt', 'content', 'tags', 'cover_path',
        'meta_title', 'meta_description', 'active', 'published_at',
    ];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['title', 'excerpt', 'content', 'tags', 'meta_title', 'meta_description'];

    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @param  'sm'|null  $size  liste için küçük kopya
     */
    public function coverUrl(?string $size = null): ?string
    {
        return ImageUploader::url($this->cover_path, $size);
    }

    /**
     * Okuma süresi (dakika), İngilizce metne göre.
     */
    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags((string) $this->content)) / 220));
    }

    /**
     * @return list<string>
     */
    public function otherLocales(): array
    {
        return array_values(array_diff($this->getTranslatedLocales('title'), ['en']));
    }
}
