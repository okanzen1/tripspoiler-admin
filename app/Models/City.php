<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;
use App\Models\Traits\HasImages;

use App\Models\Image;

class City extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;
    use HasImages;

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

    public function museums()
    {
        return $this->hasMany(Museum::class, 'city_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'city')
            ->orderBy('sort_order');
    }

    public function getImageSource(): string
    {
        return 'city';
    }
}
