<?php

namespace App\Console\Commands;

use App\Models\AffiliateClick;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Analitik ekranını denemek için sahte ziyaret/tıklama üretir.
 * Demo satırların visitor_id'si "demo_" ile başlar; --clear sadece onları siler.
 */
class AnalyticsDemo extends Command
{
    protected $signature = 'analytics:demo {--days=90 : Kaç günlük veri} {--clear : Sadece demo veriyi sil}';

    protected $description = 'Analitik ekranı için demo veri üretir (veya --clear ile siler)';

    private const PAGES = [
        ['/en', 'home', 0.18, 0.02],
        ['/en/cities/rome', 'city', 0.10, 0.05],
        ['/en/cities/paris', 'city', 0.08, 0.05],
        ['/en/cities/istanbul', 'city', 0.07, 0.04],
        ['/en/activities/colosseum-skip-the-line-tour', 'activity', 0.09, 0.16],
        ['/en/activities/vatican-museums-sistine-chapel', 'activity', 0.07, 0.14],
        ['/en/activities/eiffel-tower-summit-access', 'activity', 0.06, 0.18],
        ['/en/activities/louvre-museum-timed-entry', 'activity', 0.05, 0.12],
        ['/en/activities/bosphorus-sunset-cruise', 'activity', 0.05, 0.15],
        ['/en/activities/hagia-sophia-guided-tour', 'activity', 0.04, 0.11],
        ['/en/blog/rome-in-3-days', 'blog', 0.08, 0.07],
        ['/en/blog/paris-on-a-budget', 'blog', 0.06, 0.05],
        ['/en/blog/istanbul-hidden-gems', 'blog', 0.05, 0.06],
        ['/en/about', 'other', 0.02, 0.0],
    ];

    private const PARTNERS = ['getyourguide' => 55, 'viator' => 30, 'tiqets' => 15];
    private const SOURCES = [['google.com', null, 52], [null, null, 18], ['instagram.com', 'instagram', 12], ['pinterest.com', null, 8], ['bing.com', null, 5], ['tiktok.com', 'tiktok', 5]];
    private const DEVICES = ['mobile' => 64, 'desktop' => 31, 'tablet' => 5];
    private const COUNTRIES = ['US' => 28, 'GB' => 16, 'DE' => 11, 'TR' => 10, 'FR' => 8, 'IT' => 7, 'NL' => 6, 'ES' => 5, 'CA' => 5, 'AU' => 4];

    public function handle(): int
    {
        $deleted = Visit::where('visitor_id', 'like', 'demo_%')->delete()
            + AffiliateClick::where('visitor_id', 'like', 'demo_%')->delete();

        if ($this->option('clear')) {
            Cache::flush(); // analitik sayfası önbellekteki eski sayıları göstermesin
            $this->info("Demo veri silindi ({$deleted} satır).");

            return self::SUCCESS;
        }

        $days = max(1, (int) $this->option('days'));
        $visits = [];
        $clicks = [];

        foreach (range($days - 1, 0) as $ago) {
            $day = CarbonImmutable::today()->subDays($ago);
            // Hafta sonu daha yoğun, zamanla hafif büyüme
            $base = 140 + ($days - $ago) * 0.9;
            $visitors = (int) ($base * ($day->isWeekend() ? 1.3 : 1) * (0.85 + mt_rand() / mt_getrandmax() * 0.3));

            for ($v = 0; $v < $visitors; $v++) {
                $visitorId = 'demo_' . Str::random(20);
                $sessionId = Str::random(24);
                [$referrer, $utm] = $this->pick(self::SOURCES, fn ($s) => $s[2]);
                $device = $this->weighted(self::DEVICES);
                $country = $this->weighted(self::COUNTRIES);
                $time = $day->addSeconds(mt_rand(0, 86399));

                foreach (range(1, mt_rand(1, 4)) as $n) {
                    [$path, $type, , $clickRate] = $this->pick(self::PAGES, fn ($p) => $p[2]);
                    $time = $time->addSeconds(mt_rand(20, 240));

                    $visits[] = [
                        'visitor_id' => $visitorId, 'session_id' => $sessionId, 'path' => $path, 'page_type' => $type,
                        'referrer_host' => $n === 1 ? $referrer : null, 'utm_source' => $n === 1 ? $utm : null,
                        'country' => $country, 'device' => $device, 'locale' => 'en', 'created_at' => $time,
                    ];

                    if (mt_rand() / mt_getrandmax() < $clickRate) {
                        $partner = $this->weighted(self::PARTNERS);
                        $clicks[] = [
                            'visitor_id' => $visitorId, 'session_id' => $sessionId, 'partner' => $partner, 'path' => $path,
                            'page_type' => $type, 'target_url' => "https://www.{$partner}.com/",
                            'country' => $country, 'device' => $device, 'locale' => 'en', 'created_at' => $time->addSeconds(30),
                        ];
                    }
                }
            }

            $this->flush($visits, $clicks);
        }

        $this->flush($visits, $clicks, force: true);
        Cache::flush();
        $this->info('Demo veri oluşturuldu: ' . Visit::where('visitor_id', 'like', 'demo_%')->count() . ' görüntüleme, '
            . AffiliateClick::where('visitor_id', 'like', 'demo_%')->count() . ' tıklama.');
        $this->warn('Gerçek veri gelmeden önce silmeyi unutma: php artisan analytics:demo --clear');

        return self::SUCCESS;
    }

    private function flush(array &$visits, array &$clicks, bool $force = false): void
    {
        if ($force || count($visits) > 2000) {
            foreach (array_chunk($visits, 1000) as $chunk) {
                Visit::insert($chunk);
            }
            foreach (array_chunk($clicks, 1000) as $chunk) {
                AffiliateClick::insert($chunk);
            }
            $visits = $clicks = [];
        }
    }

    private function weighted(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    private function pick(array $items, callable $weight): array
    {
        $total = array_sum(array_map($weight, $items));
        $roll = mt_rand() / mt_getrandmax() * $total;
        foreach ($items as $item) {
            if (($roll -= $weight($item)) <= 0) {
                return $item;
            }
        }

        return end($items);
    }
}
