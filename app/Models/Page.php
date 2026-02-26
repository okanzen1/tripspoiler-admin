<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'slug',
    ];

    public function images()
    {
        $source = $this->slug . '_page';

        return $this->hasMany(Image::class, 'source_id')
            ->where('source', $source)
            ->orderBy('sort_order');
    }

    public function getImageSource(): string
    {
        return $this->slug . '_page';
    }
}
