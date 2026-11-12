<?php

namespace App\Models;

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
    use HasSlugRedirects, HasTranslations;

    /** Front'taki adres kalıbı (config/site.php) */
    public const SITE_PATH = 'activity';

    protected $fillable = [
        'city_id', 'partner_id', 'name', 'slug', 'type', 'affiliate_url', 'partner_product_id',
        'duration', 'audio_guide', 'setting', 'description', 'meta_title', 'meta_description',
        'is_popular', 'active', 'sort_order',
    ];

    /** Çok dilli alanlar; panel İngilizce (en) düzenler, diğer diller korunur */
    public array $translatable = ['name', 'description', 'meta_title', 'meta_description'];

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
