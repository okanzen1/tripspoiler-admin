<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Models\Traits\HasImages;

class PageContent extends Model
{
    use HasTranslations;
    use HasImages;

    protected $fillable = [
        'page_id',
        'city_id',
        'content',
        'meta_title',
        'meta_description',
        'is_active',
    ];

    public $translatable = [
        'content',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** Image sistemi için */
    public function getImageSource(): string
    {
        return 'page_content';
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'page_content')
            ->orderBy('sort_order');
    }
}
