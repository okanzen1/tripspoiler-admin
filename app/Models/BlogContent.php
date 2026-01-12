<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Models\Traits\HasImages;

class BlogContent extends Model
{
    use HasTranslations;
    use HasImages;

    protected $fillable = [
        'blog_id',
        'title',
        'content',
        'status',
        'sort_order',
    ];

    public $translatable = [
        'title',
        'content',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function getImageSource(): string
    {
        return 'blog_content';
    }

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'source_id')
            ->where('source', 'blog_content')
            ->orderBy('sort_order');
    }
}
