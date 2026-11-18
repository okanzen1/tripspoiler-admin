<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedSlugs;
use App\Models\Concerns\HasSlugRedirects;
use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class Activity extends Model
{
    use HasLocalizedSlugs, HasSlugRedirects, HasTranslations;

    /** Dillere göre adres bu alanın çevirisinden üretilir (HasLocalizedSlugs) */
    public const SLUG_SOURCE = 'name';

    /** Front'taki adres kalıbı (config/site.php) */
    public const SITE_PATH = 'activity';

    protected $fillable = [
        'city_id', 'partner_id', 'name', 'slug', 'type', 'affiliate_url', 'partner_product_id',
        'duration', 'audio_guide', 'setting', 'description', 'meta_title', 'meta_description',
        'is_popular', 'active', 'sort_order',
        'highlights', 'included', 'excluded', 'meeting_point', 'important_info', 'cancellation',
    ];

    /**
     * Sayfadaki ayrı bloklar (İngilizce yazılır, çevrilir): alan → [paneldeki ad, açıklama, liste mi (her satır bir madde)].
     */
    public const DETAILS = [
        'highlights' => ['Öne çıkanlar', 'Her satıra bir madde. Örn: Skip the ticket line at the Acropolis', true],
        'included' => ['Dahil olanlar', 'Her satıra bir madde. Örn: Entry ticket, Audio guide', true],
        'excluded' => ['Dahil olmayanlar', 'Her satıra bir madde. Örn: Hotel pickup, Food and drinks', true],
        'meeting_point' => ['Buluşma / giriş noktası', 'Kısa metin: nereye gidilir, adres. Örn: Main entrance on Theorias Street', false],
        'important_info' => ['Gitmeden önce bilin', 'Her satıra bir madde. Örn: Closed on Tuesdays', true],
        'cancellation' => ['İptal koşulları', 'Partnerin sayfasında yazanı yaz; emin değilsen boş bırak. Örn: Free cancellation up to 24 hours before', false],
    ];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['name', 'description', 'meta_title', 'meta_description',
        'highlights', 'included', 'excluded', 'meeting_point', 'important_info', 'cancellation'];

    /**
     * Panelin dili Türkçe olsa da aktivite metinleri İngilizce (en) okunur ve yazılır.
     */
    public function getLocale(): string
    {
        return 'en';
    }

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'audio_guide' => 'boolean',
            'is_popular' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ActivityImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cover(): HasOne
    {
        return $this->hasOne(ActivityImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Çevirisi olan diller (en hariç), ör. ["de", "es", "tr"].
     *
     * @return list<string>
     */
    public function otherLocales(): array
    {
        return array_values(array_diff($this->getTranslatedLocales('name'), ['en']));
    }
}
