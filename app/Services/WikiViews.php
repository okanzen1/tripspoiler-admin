<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Wikipedia ilgisi: İngilizce Wikipedia sayfasının son 30 gündeki okunma sayısı (Wikimedia'nın ücretsiz, anahtarsız verisi).
 * Önce sayfanın gerçek adı bulunur (yönlendirmeler izlenir), sonra günlük okunmalar toplanır.
 */
class WikiViews
{
    public const DAYS = 30;

    /** "https://en.wikipedia.org/wiki/Galata_Tower" ya da "Galata Tower" → "Galata Tower" */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('~wikipedia\.org/wiki/([^?#]+)~i', $value, $m)) {
            $value = rawurldecode($m[1]);
        }
        $value = trim(str_replace('_', ' ', $value));

        return $value === '' ? null : Str::limit($value, 190, '');
    }

    /** @return array{title: string, views: int}|null  sayfa yoksa null */
    public static function fetch(string $title): ?array
    {
        $pages = self::http()->get('https://en.wikipedia.org/w/api.php', [
            'action' => 'query', 'format' => 'json', 'formatversion' => 2, 'redirects' => 1, 'titles' => $title,
        ])->throw()->json('query.pages', []);

        $page = $pages[0] ?? null;
        if (!$page || isset($page['missing']) || isset($page['invalid'])) {
            return null;
        }

        $end = now()->subDay();
        $start = now()->subDays(self::DAYS);
        $response = self::http()->get(sprintf(
            'https://wikimedia.org/api/rest_v1/metrics/pageviews/per-article/en.wikipedia.org/all-access/user/%s/daily/%s/%s',
            rawurlencode(str_replace(' ', '_', $page['title'])), $start->format('Ymd'), $end->format('Ymd'),
        ));

        // 404: sayfa yeni ya da hiç okunmamış
        $views = $response->status() === 404 ? 0 : (int) collect($response->throw()->json('items', []))->sum('views');

        return ['title' => $page['title'], 'views' => $views];
    }

    /** Aktivitenin sayısını günceller; sayfa bulunamazsa sayı boşalır. */
    public static function refresh(Activity $activity): void
    {
        if (blank($activity->wiki_title)) {
            $activity->forceFill(['wiki_views' => null, 'wiki_views_at' => null])->saveQuietly();

            return;
        }

        $result = self::fetch($activity->wiki_title);
        $activity->forceFill([
            'wiki_title' => $result['title'] ?? $activity->wiki_title,
            'wiki_views' => $result['views'] ?? null,
            'wiki_views_at' => now(),
        ])->saveQuietly();
    }

    private static function http()
    {
        // Wikimedia, istek yapan siteyi tanıtan bir User-Agent ister
        return Http::withUserAgent('TripSpoiler/1.0 (+' . rtrim((string) config('app.url'), '/') . ')')
            ->acceptJson()->timeout(15)->retry(2, 1000, throw: false);
    }
}
