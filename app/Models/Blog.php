<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasTranslatableSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Blog extends Model
{
    use HasTranslations;
    use HasTranslatableSlug;

    protected $fillable = [
        'title',
        'excerpt',
        'slug',
        'meta_title',
        'meta_description',
        'themes',
        'city_id',
        'source',
        'source_id',
        'sort_order',
        'status',
        'click_count',
    ];

    public $translatable = [
        'title',
        'excerpt',
        'themes',
        'slug',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'themes' => 'array',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function content()
    {
        return $this->hasOne(BlogContent::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'blog')
            ->orderBy('sort_order');
    }

}
