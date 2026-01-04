<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'active',
    ];

    protected $casts = [
        'name' => 'array',
        'slug' => 'array',
        'active' => 'boolean',
    ];
}
