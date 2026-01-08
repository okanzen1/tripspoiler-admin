<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class City extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;

    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'active',
    ];

    public $translatable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Slug ayarları (City için).
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
