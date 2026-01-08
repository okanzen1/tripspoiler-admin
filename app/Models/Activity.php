<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Activity extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'country_id',
        'museum_id',
        'sort_order',
        'status',
    ];

    public $translatable = [
        'name',
        'slug',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
