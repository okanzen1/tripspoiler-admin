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
        'meta_title',
        'meta_description',
        'activity_type',
        'city_id',
        'sort_order',
        'affiliate_link',
        'affiliate_id',
        'most_popular',
        'status',
        'duration',
        'audio_guide',
        'description',
        'source_product_id',
    ];

    public $translatable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'duration',
        'description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'audio_guide' => 'boolean',
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

    public function blogs()
    {
        return $this->belongsToMany(Blog::class);
    }
}
