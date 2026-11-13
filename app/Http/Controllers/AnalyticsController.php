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
        $days = (int) $request->get('range', 30);
        $analytics = new Analytics(array_key_exists($days, Analytics::RANGES) ? $days : 30);

        // Etiketler (tarih, ülke, cihaz adları) panel diline göre değiştiği için dil de anahtarda
        $key = 'analytics:' . $analytics->days . ':' . app()->getLocale() . ':' . $analytics->end->toDateString();
        $data = Cache::remember($key, self::CACHE_SECONDS, fn () => [
            'kpis' => $analytics->kpis(),
            'daily' => $analytics->daily(),
            'topPages' => $analytics->topPages(),
            'partners' => $analytics->partners(),
            'sources' => $analytics->sources(),
            'devices' => $analytics->devices(),
            'countries' => $analytics->countries(),
        ]);

        $filters = $this->liveFilters($request);

        return view('admin.analytics.index', ['analytics' => $analytics, 'live' => Analytics::live($filters), 'liveFilters' => $filters, ...$data]);
    }

    /** Canlı bölüm: sayfa 10 saniyede bir (ve filtre değişince) bunu çekip yerine koyar (önbelleksiz) */
    public function live(Request $request)
    {
        return view('admin.analytics._live', ['live' => Analytics::live($this->liveFilters($request))]);
    }

    /** Tek ziyaretçi (IP): bütün oturumları, sayfaları ve partner tıklamaları */
    public function visitor(string $who)
    {
        $data = Analytics::visitor($who);
        abort_if($data['visits']->isEmpty() && $data['clicks']->isEmpty(), 404);

        return view('admin.analytics.visitor', ['who' => $who, ...$data]);
    }

    /** Canlı liste filtreleri: ara, aralık, ülke, cihaz */
    private function liveFilters(Request $request): array
    {
        return [
            'q' => mb_substr(trim((string) $request->query('q')), 0, 100) ?: null,
            'range' => array_key_exists((int) $request->query('when'), Analytics::LIVE_RANGES) ? (int) $request->query('when') : 0,
            'country' => preg_match('/^[A-Z]{2}$/', (string) $request->query('country')) ? $request->query('country') : null,
            'device' => in_array($request->query('device'), ['desktop', 'mobile', 'tablet'], true) ? $request->query('device') : null,
        ];
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
