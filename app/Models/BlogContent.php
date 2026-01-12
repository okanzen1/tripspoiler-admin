<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class BlogContent extends Model
{
    use HasTranslations;

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

    public function blog()
    {
        return $this->belongsTo(Blog::class);
    }
}
