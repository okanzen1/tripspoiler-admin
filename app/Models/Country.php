<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Country extends Model
{
    protected $fillable = ['code', 'name', 'slug', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Panel dilindeki ada göre alfabetik sıralar (Ç, Ş, Ü vb. doğru yerde).
     *
     * @param  Collection<int, Country>  $countries
     * @return Collection<int, Country>
     */
    public static function sortByTurkishName(Collection $countries): Collection
    {
        $collator = new \Collator(app()->getLocale());

        return $countries->sort(fn (Country $a, Country $b) => $collator->compare($a->name_tr, $b->name_tr))->values();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * Panelde gösterilen ad, panel dilinde (sitede İngilizce `name` kullanılır).
     */
    protected function nameTr(): Attribute
    {
        return Attribute::get(fn () => \Locale::getDisplayRegion("-{$this->code}", app()->getLocale()) ?: $this->name);
    }

    protected function flag(): Attribute
    {
        // Ülke kodundan bayrak emojisi (regional indicator harfleri)
        return Attribute::get(fn () => implode('', array_map(
            fn (string $char) => mb_chr(0x1F1E6 + ord($char) - ord('A')),
            str_split($this->code),
        )));
    }
}
