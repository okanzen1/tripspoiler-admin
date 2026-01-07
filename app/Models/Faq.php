<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'source',
        'source_id',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'question' => 'array',
        'answer' => 'array',
        'status' => 'boolean',
    ];
}
