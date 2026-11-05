<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sayfa banner resmi: page_key (activities | cities), city_id boşsa sayfanın genel resmi.
 * image = masaüstü, image_mobile = telefon (isteğe bağlı; yoksa masaüstü kullanılır).
 */
class PageBanner extends Model
{
    protected $fillable = ['page_key', 'city_id', 'image', 'image_mobile'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
