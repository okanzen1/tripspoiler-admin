<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    protected $fillable = [
        'path',
        'source',
        'source_id',
        'sort_order',
    ];

    public function getUrlAttribute(): string
    {
        return rtrim(config('media.front_url'), '/') . '/media/' . $this->id;
    }
}
