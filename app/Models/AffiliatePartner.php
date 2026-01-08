<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AffiliatePartner extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'active',
    ];

    public $translatable = [
        'name',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
