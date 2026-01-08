<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasTranslations;

    protected $fillable = [
        'question',
        'answer',
        'source',
        'source_id',
        'sort_order',
        'status',
    ];

    public $translatable = [
        'question',
        'answer',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
