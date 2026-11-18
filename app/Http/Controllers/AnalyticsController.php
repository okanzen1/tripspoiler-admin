<?php

namespace App\Http\Controllers;

use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    /** Hesaplar büyük tabloları taradığı için sonuç kısa süre saklanır */
    private const CACHE_SECONDS = 300;

    public function index(Request $request)
    {
        [$analytics, $filters] = $this->resolve($request);

        // Etiketler (tarih, ülke, cihaz adları) panel diline göre değiştiği için dil de anahtarda; sürüm: kayıt silinince artar
        $key = 'analytics:' . Cache::get('analytics:version', 0) . ':' . $analytics->start->toDateString() . ':' . $analytics->end->toDateString()
            . ':' . ($filters['device'] ?? '-') . ':' . ($filters['country'] ?? '-') . ':' . app()->getLocale();
        $data = Cache::remember($key, self::CACHE_SECONDS, fn () => [
            'kpis' => $analytics->kpis(),
            'daily' => $analytics->daily(),
            'topPages' => $analytics->topPages(),
            'partners' => $analytics->partners(),
            'sources' => $analytics->sources(),
            'devices' => $analytics->devices(),
            'countries' => $analytics->countries(),
        ]);

        return view('admin.analytics.index', ['analytics' => $analytics, 'live' => Analytics::live($filters), 'filters' => $filters, ...$data]);
    }

    /** Canlı bölüm: sayfa 10 saniyede bir bunu (sayfayla aynı filtrelerle) çekip yerine koyar (önbelleksiz) */
    public function live(Request $request)
    {
        return view('admin.analytics._live', ['live' => Analytics::live($this->resolve($request)[1])]);
    }

    /** Tek ziyaretçi (IP): bütün oturumları, sayfaları ve partner tıklamaları */
    public function visitor(string $who)
    {
        $data = Analytics::visitor($who);
        abort_if($data['visits']->isEmpty() && $data['clicks']->isEmpty(), 404);

        return view('admin.analytics.visitor', ['who' => $who, ...$data]);
    }

    /**
     * Sayfanın üstündeki tek filtre çubuğu: dönem (bugün / son 7-30-90 gün ya da takvimden from–to), cihaz, ülke, arama.
     * Kartlar, grafik, tablolar ve ziyaretçi listesi aynı filtreyi kullanır.
     *
     * @return array{0: Analytics, 1: array{q: ?string, device: ?string, country: ?string, range: ?int, from: string, to: string, custom: bool}}
     */
    private function resolve(Request $request): array
    {
        $date = fn ($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? \Carbon\CarbonImmutable::createFromFormat('Y-m-d', $v)->startOfDay() : null;
        [$from, $to] = [$date($request->query('from')), $date($request->query('to'))];
        if ($from && $to && abs($from->diffInDays($to)) > 366) {
            $from = $to->subDays(366);
        }
        $custom = $from && $to;
        $days = (int) $request->query('range', 1); // ilk açılışta bugün
        $days = array_key_exists($days, Analytics::RANGES) ? $days : 1;
        $device = in_array($request->query('device'), ['desktop', 'mobile', 'tablet'], true) ? $request->query('device') : null;
        $country = preg_match('/^[A-Z]{2}$/', (string) $request->query('country')) ? $request->query('country') : null;

        $analytics = new Analytics($days, $custom ? $from : null, $custom ? $to : null, ['device' => $device, 'country' => $country]);

        return [$analytics, [
            'q' => mb_substr(trim((string) $request->query('q')), 0, 100) ?: null,
            'device' => $device,
            'country' => $country,
            'range' => $custom ? null : $days,
            'from' => $analytics->start->toDateString(),
            'to' => $analytics->end->toDateString(),
            'custom' => (bool) $custom,
        ]];
    }

    /**
     * Sayılmayan IP'ler (sitede bu adreslerden gelen ziyaretler analitiğe yazılmaz): "Benim IP'mi ekle" ya da kaldır.
     */
    public function ignoreIp(\Illuminate\Http\Request $request)
    {
        $data = $request->validate(['ip' => ['required', 'ip'], 'remove' => ['boolean']]);
        $list = array_values(array_filter(array_map('trim', explode(',', (string) \App\Models\Setting::get('analytics_ignore_ips')))));
        $list = $request->boolean('remove') ? array_values(array_diff($list, [$data['ip']])) : array_values(array_unique([...$list, $data['ip']]));
        \App\Models\Setting::put('analytics_ignore_ips', $list ? implode(',', array_slice($list, -20)) : null);

        return back()->with('success', $request->boolean('remove')
            ? __(':ip yeniden sayılıyor.', ['ip' => $data['ip']])
            : __(':ip artık analitikte sayılmıyor.', ['ip' => $data['ip']]));
    }
}
