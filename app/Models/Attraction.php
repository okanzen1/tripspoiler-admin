<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasSlugRedirects;
use Spatie\Translatable\HasTranslations;

/**
 * Yer sayfası (Topkapı Sarayı, Akropolis…): sitede /{dil}/attractions/{slug}.
 * Biletler = bağlı aktiviteler (bir kombo birden fazla yere bağlanabilir). Metinler İngilizce yazılır, Çeviriler'den çevrilir.
 */
class Attraction extends Model
{
    use HasSlugRedirects, HasTranslations;

    /** Adres değişince eski adres her dilde yeniye yönlenir (config/site.php) */
    public const SITE_PATH = 'attraction';

    /** Ziyaret bilgileri: alan → [paneldeki ad, örnek, liste mi (her satır bir madde)] */
    public const VISIT_FIELDS = [
        'opening_hours' => ['Açılış saatleri', 'Wednesday–Monday 09:00–18:00 (last entry 17:00)', false],
        'closed_days' => ['Kapalı günler', 'Tuesdays; 1 January', false],
        'getting_there' => ['Nasıl gidilir', 'T1 tram to Sultanahmet or Gülhane, then a 5-minute walk', false],
        'dress_code' => ['Kıyafet', 'No dress code / Shoulders and knees covered', false],
        'good_to_know' => ['Gitmeden önce bilin', 'Her satıra bir madde. Örn: Security checks can take 30 minutes in summer', true],
    ];

    protected $fillable = ['city_id', 'slug', 'name', 'summary', 'content', 'opening_hours', 'closed_days', 'getting_there', 'dress_code',
        'good_to_know', 'meta_title', 'meta_description', 'official_url', 'image_path', 'image_focus', 'active', 'sort_order', 'activity_ids'];

    public array $translatable = ['name', 'summary', 'content', 'opening_hours', 'closed_days', 'getting_there', 'dress_code',
        'good_to_know', 'meta_title', 'meta_description'];

    /** Kaydedilince bağlanacak aktiviteler (panel formu ve MCP "activity_ids": [1,2] ya da "1,2") */
    private ?array $pendingActivityIds = null;

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer', 'image_focus' => 'integer'];
    }

    public function getLocale(): string
    {
        return 'en';
    }

    protected static function booted(): void
    {
        static::saved(function (Attraction $attraction) {
            if ($attraction->pendingActivityIds !== null) {
                $attraction->activities()->sync($attraction->pendingActivityIds);
                $attraction->pendingActivityIds = null;
            }
        });
    }

    public function setActivityIdsAttribute(array|string|null $value): void
    {
        $ids = is_array($value) ? $value : preg_split('/[\s,]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        $this->pendingActivityIds = array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    public function getActivityIdsAttribute(): array
    {
        return $this->exists ? $this->activities()->pluck('activities.id')->all() : [];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(?string $size = null): ?string
    {
        return \App\Services\ImageUploader::url($this->image_path, $size);
    }
}
