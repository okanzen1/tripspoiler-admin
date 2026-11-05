<?php

namespace App\Support;

/**
 * Sunucunun kabul ettiği yükleme boyutu (php.ini). Panel, görselleri bu sınıra göre tarayıcıda küçültür
 * (resources/js/image-compress.js); böylece sunucu ayarı düşük olsa da yükleme reddedilmez.
 */
class UploadLimit
{
    /** Tek dosya: upload_max_filesize */
    public static function fileBytes(): int
    {
        return min(self::bytes(ini_get('upload_max_filesize')), self::requestBytes());
    }

    /** Bir formdaki tüm dosyaların toplamı: post_max_size */
    public static function requestBytes(): int
    {
        return self::bytes(ini_get('post_max_size'));
    }

    private static function bytes(string|false $value): int
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0' || $value === '-1') {
            return PHP_INT_MAX;
        }

        return (int) $value * match (strtolower(substr($value, -1))) {
            'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1,
        };
    }
}
