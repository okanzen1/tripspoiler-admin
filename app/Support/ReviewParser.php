<?php

namespace App\Support;

/**
 * Yapıştırılan yorumları satır satır ayırır (Yorumlar › "Toplu ekle"; aynısı tarayıcıda resources/js/review-bulk.js).
 * Her yorum tek satır, parçalar "|" ile ayrılır:
 *   1. Lucía Martínez | 4★ | Bien organizada — Me gustó… | es | 2026-07-29
 * Sıra: ad | puan | yorum | dil (isteğe bağlı) | tarih (isteğe bağlı). Puan "4", "4★" ya da "4/5" olabilir.
 */
class ReviewParser
{
    /**
     * @return array{reviews: list<array{name: string, rating: int, comment: string, date: ?string, language: ?string}>, invalid: list<int>}
     *         invalid: okunamayan satırların numaraları (1'den başlar)
     */
    public static function parse(string $text): array
    {
        $reviews = [];
        $invalid = [];
        foreach (preg_split('/\R/u', $text) as $i => $line) {
            $line = trim(preg_replace('/[\s\x{00A0}\x{2000}-\x{200B}\x{202F}\x{FEFF}]+/u', ' ', str_replace('**', '', $line)));
            if ($line === '') {
                continue;
            }
            $review = self::line($line);
            if ($review) {
                $reviews[] = $review;
            } else {
                $invalid[] = $i + 1;
            }
        }

        return ['reviews' => $reviews, 'invalid' => $invalid];
    }

    private static function line(string $line): ?array
    {
        $line = preg_replace('/^\d+ ?[.)-] ?/u', '', $line); // baştaki "1. "
        $parts = array_map('trim', explode('|', $line));
        if (count($parts) < 3) {
            return null;
        }

        $name = array_shift($parts);
        if (!preg_match('/^([1-5])(?: ?(?:★|⭐|\*|\/ ?5|stars?|yıldız))?$/iu', (string) array_shift($parts), $m)) {
            return null;
        }
        $rating = (int) $m[1];

        // sondan: tarih ve dil (ikisi de isteğe bağlı)
        $date = null;
        $language = null;
        if ($parts && preg_match('/^\d{4}-\d{2}-\d{2}$/', end($parts)) && checkdate((int) substr(end($parts), 5, 2), (int) substr(end($parts), 8, 2), (int) substr(end($parts), 0, 4))) {
            $date = array_pop($parts);
        }
        if (count($parts) > 1 && preg_match('/^[a-z]{2}(?:-[A-Za-z]{2})?$/', end($parts))) {
            $language = strtolower(array_pop($parts));
        }

        $comment = trim(implode(' | ', $parts)); // yorumun içinde "|" geçerse bozulmasın
        if ($name === '' || $comment === '') {
            return null;
        }

        return ['name' => $name, 'rating' => $rating, 'comment' => $comment, 'date' => $date, 'language' => $language];
    }
}
