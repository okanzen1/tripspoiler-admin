<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Models\Traits\HasImages;

class CityExperienceCategoryDescription extends Model
{
    use HasTranslations;
    use HasImages;

    protected $fillable = [
        'city_experience_category_id',
        'description',
    ];

    public $translatable = [
        'description',
    ];

    public function getImageSource(): string
    {
        return 'city_experience_category_description';
    }

    public function category()
    {
        return $this->belongsTo(
            CityExperienceCategory::class,
            'city_experience_category_id'
        );
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'city_experience_category_description')
            ->orderBy('sort_order');
    }
}