<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Traits\HasImages;
use Spatie\Translatable\HasTranslations;


class Activity extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;
    use HasImages;

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'museum_id',
        'sort_order',
        'affiliate_link',
        'affiliate_id',
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

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'activity')
            ->orderBy('sort_order');
    }

    public function affiliatePartner()
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_id');
    }

    public function getImageSource(): string
    {
        return 'activity';
    }
}
