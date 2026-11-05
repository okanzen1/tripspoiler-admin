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

        return view('admin.analytics.index', ['analytics' => $analytics, ...$data]);
    }
}
