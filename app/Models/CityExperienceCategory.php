<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CityExperienceCategory extends Model
{
    use HasTranslations;

    protected $fillable = [
        'page_content_id',
        'name',
        'sort_order',
        'status',
    ];

    public $translatable = ['name'];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function pageContent()
    {
        return $this->belongsTo(PageContent::class);
    }

    public function descriptions()
    {
        return $this->hasMany(
            CityExperienceCategoryDescription::class,
            'city_experience_category_id'
        )->orderBy('id');
    }
}