<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use App\Models\Traits\HasImages;

class Venue extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;
    use HasImages;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'city_id',
        'museum_id',
        'affiliate_id',
        'affiliate_link',
        'sources',
        'source_ids',
        'status',
        'sort_order',
    ];

    public $translatable = [
        'name',
        'description',
        'slug',
    ];

    protected $casts = [
        'status' => 'boolean',
        'source_ids' => 'array',
        'sources' => 'array',
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

    public function museum()
    {
        return $this->belongsTo(Museum::class);
    }

    public function affiliatePartner()
    {
        return $this->belongsTo(AffiliatePartner::class, 'affiliate_id');
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'venue')
            ->orderBy('sort_order');
    }

    public function getImageSource(): string
    {
        return 'venue';
    }
}
