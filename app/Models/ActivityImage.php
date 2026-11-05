<?php

namespace App\Models;

use App\Services\ImageUploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityImage extends Model
{
    protected $fillable = ['activity_id', 'path', 'width', 'height', 'sort_order'];

    protected static function booted(): void
    {
        // Kayıt silinince dosyaları da silinir (işlem içindeyse işlem bitince)
        static::deleted(fn (self $image) => app(ImageUploader::class)->delete($image->path));
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @param  'sm'|null  $size  liste kartları için küçük kopya
     */
    public function url(?string $size = null): ?string
    {
        return ImageUploader::url($this->path, $size);
    }
}
