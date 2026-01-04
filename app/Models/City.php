<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'active',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'active' => 'boolean',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
