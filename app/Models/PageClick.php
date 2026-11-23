<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Sayfadaki bir tıklama (sıcaklık haritası). Site yazar (tripspoiler: TrackLeaveController).
 * x: soldan piksel, y: sayfanın en üstünden piksel, vw: o anki ekran genişliği, ph: sayfanın boyu.
 */
class PageClick extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $casts = ['created_at' => 'datetime'];

    public static function hash(string $path): string
    {
        return sha1($path);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subMonths(Visit::KEEP_MONTHS));
    }
}
