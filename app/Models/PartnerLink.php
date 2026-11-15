<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aktivite listesinin sonundaki "Aradığını bulamadın mı?" kartı: partnerin şehir sayfasına doğrudan bağlantı.
 * city_id boş = bütün şehirler. Site: tripspoiler/resources/views/activities/index.blade.php
 */
class PartnerLink extends Model
{
    protected $fillable = ['city_id', 'partner_id', 'type', 'url', 'count_label', 'image_path', 'image_focus', 'active', 'sort_order'];

    /** Hangi sekmede çıkar (boş = hepsinde); sitedeki aktivite türleriyle aynı */
    public const TYPES = ['pass' => 'Şehir kartları', 'product' => 'Deneyimler', 'package' => 'Paketler'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function imageUrl(?string $size = null): ?string
    {
        return \App\Services\ImageUploader::url($this->image_path, $size);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
