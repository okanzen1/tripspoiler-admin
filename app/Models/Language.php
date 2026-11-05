<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Sitenin dili. "en" kaynak dildir: panelde yazılır, diğerleri ondan çevrilir.
 */
class Language extends Model
{
    public const SOURCE = 'en';

    protected $fillable = ['code', 'name', 'native_name', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Çevrilecek diller: aktif olup kaynak dil olmayanlar.
     *
     * @return Collection<int, Language>
     */
    public static function targets(): Collection
    {
        return static::active()->where('code', '!=', self::SOURCE)->ordered()->get();
    }

    /** Dil seçicilerde gösterilen bayrak */
    private const FLAGS = [
        'en' => '🇬🇧', 'tr' => '🇹🇷', 'de' => '🇩🇪', 'fr' => '🇫🇷', 'es' => '🇪🇸', 'it' => '🇮🇹',
        'pt' => '🇵🇹', 'ru' => '🇷🇺', 'ar' => '🇸🇦', 'zh' => '🇨🇳', 'ja' => '🇯🇵', 'ko' => '🇰🇷',
    ];

    public function flag(): string
    {
        return self::FLAGS[$this->code] ?? '🌐';
    }

    public function isSource(): bool
    {
        return $this->code === self::SOURCE;
    }
}
