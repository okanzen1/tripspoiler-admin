<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Museum extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'city_id',
        'country_id',
        'sort_order',
        'status',
    ];

    public $translatable = [
        'name',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
