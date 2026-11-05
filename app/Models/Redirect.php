<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eski adres → yeni adres (301). Front her istekte from_path'e bakar.
 */
class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_path'];

    protected function casts(): array
    {
        return ['last_hit_at' => 'datetime'];
    }

    /**
     * Yönlendirme ekler. Zincir oluşmasın diye eski adrese giden yönlendirmeler de yeni adrese çevrilir;
     * yeni adres daha önce yönlendirildiyse (slug geri alındı) o yönlendirme silinir.
     */
    public static function point(string $from, string $to): void
    {
        $from = self::normalize($from);
        $to = self::normalize($to);
        if ($from === $to) {
            return;
        }

        static::where('to_path', $from)->update(['to_path' => $to]);
        static::where('from_path', $to)->delete();
        static::where('from_path', $from)->whereColumn('from_path', 'to_path')->delete();
        static::updateOrCreate(['from_path' => $from], ['to_path' => $to]);
    }

    /**
     * Baştaki / ile, sondaki / olmadan, % kodları çözülmüş yol.
     */
    public static function normalize(string $path): string
    {
        // parse_url çok baytlı karakterleri (中文, العربية) bozduğu için elle: alan adı, sorgu ve # atılır
        $path = preg_replace(['#^[a-z][a-z0-9+.-]*://[^/]*#i', '/[?#].*$/s'], '', trim($path));
        $path = rawurldecode($path);

        return '/' . trim($path, '/');
    }
}
