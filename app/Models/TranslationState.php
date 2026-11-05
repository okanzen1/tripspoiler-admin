<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Bir kaydın bir dile hangi İngilizce metinden (source_hash) çevrildiği.
 */
class TranslationState extends Model
{
    public $timestamps = false;

    protected $fillable = ['translatable_type', 'translatable_id', 'locale', 'source_hash', 'translated_at'];

    protected function casts(): array
    {
        return ['translated_at' => 'datetime'];
    }

    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }
}
