<?php

namespace App\Models;

use App\Models\Concerns\HasSlugRedirects;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasSlugRedirects;

    /** Front'taki adres kalıbı (config/site.php) */
    public const SITE_PATH = 'city';

    protected $fillable = ['country_id', 'geoname_id', 'name', 'slug', 'population', 'latitude', 'longitude', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /**
     * Kısa nüfus etiketi: "3,1 milyon", "335 bin".
     */
    public function populationLabel(): ?string
    {
        return match (true) {
            !$this->population => null,
            $this->population >= 1_000_000 => number_format($this->population / 1_000_000, 1, ',', '.') . ' milyon',
            $this->population >= 1_000 => number_format($this->population / 1_000, 0, ',', '.') . ' bin',
            default => (string) $this->population,
        };
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(Blog::class);
    }

    public function guide(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CityGuide::class);
    }

    public function guideSections(): HasMany
    {
        return $this->hasMany(CityGuideSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Sitede görünen şehirler: kendisi ve ülkesi aktif olanlar.
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('active', true)->whereHas('country', fn ($q) => $q->where('active', true));
    }

    /**
     * Şehir aktif ama ülkesi pasifse sitede görünmez; panelde uyarı göstermek için.
     */
    public function isHiddenByCountry(): bool
    {
        return $this->active && !$this->country->active;
    }
}
