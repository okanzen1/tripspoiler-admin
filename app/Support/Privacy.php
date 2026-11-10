<?php

namespace App\Support;

/**
 * Kişisel veri veritabanında hiçbir zaman açık durmaz: değer şifreli saklanır (Laravel "encrypted" cast,
 * APP_KEY — site ve panel aynı anahtarı kullanır), aramak / gruplamak için yanına tek yönlü parmak izi yazılır.
 * Parmak izi HMAC-SHA256 (anahtar APP_KEY): anahtar olmadan tahmin edilemez, geri çözülemez.
 * İki projede aynı dosya: tripspoiler/app/Support/Privacy.php ve tripspoiler-admin/app/Support/Privacy.php
 */
class Privacy
{
    public static function hash(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
