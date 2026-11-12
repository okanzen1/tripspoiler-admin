<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Resim çöp kutusu: silinen resimler (gece temizliği media:prune ya da panelde değiştirilen/silinen resim) hemen silinmez,
 * media diskinde "_trash/{gün}/{asıl yol}" altına taşınır ve 30 gün durur. Panel › Yönetim › Silinen resimler'den
 * geri alınabilir; 30 günü dolanlar gece temizliğinde kalıcı silinir.
 */
class MediaTrash
{
    public const DIR = '_trash';

    public const DAYS = 30;

    /** Dosyaları çöp kutusuna taşır (olmayanlar yok sayılır) */
    public static function trash(array $files): void
    {
        $disk = Storage::disk('media');
        $day = now()->format('Y-m-d');
        foreach ($files as $file) {
            if (!$file || str_starts_with($file, self::DIR . '/') || !$disk->exists($file)) {
                continue;
            }
            $target = self::DIR . "/{$day}/{$file}";
            if ($disk->exists($target)) {
                $disk->delete($target);
            }
            $disk->move($file, $target);
        }
    }

    /**
     * Çöp kutusundaki resimler (küçük/orta kopyalar ayrıca listelenmez), en yenisi üstte.
     *
     * @return list<array{trash: string, path: string, day: string, expires: string, url: string, size: int}>
     */
    public static function items(): array
    {
        $disk = Storage::disk('media');
        $items = [];
        foreach ($disk->allFiles(self::DIR) as $file) {
            if (preg_match('/-(sm|md)\.webp$/', $file) || !preg_match('#^' . self::DIR . '/(\d{4}-\d{2}-\d{2})/(.+)$#', $file, $m)) {
                continue;
            }
            $items[] = [
                'trash' => $file,
                'path' => $m[2],
                'day' => $m[1],
                'expires' => Carbon::parse($m[1])->addDays(self::DAYS)->toDateString(),
                'url' => $disk->url($file),
                'size' => $disk->size($file),
            ];
        }
        usort($items, fn ($a, $b) => strcmp($b['day'], $a['day']) ?: strcmp($a['path'], $b['path']));

        return $items;
    }

    /** Resmi (ve kopyalarını) eski yerine geri koyar; o yolda başka dosya varsa dokunmaz */
    public static function restore(string $trashFile): ?string
    {
        if (!preg_match('#^' . self::DIR . '/(\d{4}-\d{2}-\d{2})/(.+)$#', $trashFile, $m) || str_contains($m[2], '..')) {
            return null;
        }
        $disk = Storage::disk('media');
        $day = $m[1];
        $base = preg_replace('/-(sm|md)(\.webp)$/', '$2', $m[2]);
        $restored = null;
        foreach (array_unique([$base, ...\App\Services\ImageUploader::files($base), $m[2]]) as $file) {
            $from = self::DIR . "/{$day}/{$file}";
            if ($disk->exists($from) && !$disk->exists($file)) {
                $disk->move($from, $file);
                $restored ??= $file;
            }
        }

        return $restored;
    }

    /** 30 günü dolan günlerin klasörlerini kalıcı siler; silinen dosya sayısı */
    public static function purge(): int
    {
        $disk = Storage::disk('media');
        $limit = now()->subDays(self::DAYS)->toDateString();
        $count = 0;
        foreach ($disk->directories(self::DIR) as $dir) {
            $day = basename($dir);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) && $day < $limit) {
                $count += count($disk->allFiles($dir));
                $disk->deleteDirectory($dir);
            }
        }

        return $count;
    }
}
