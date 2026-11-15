<?php

namespace App\Http\Controllers;

use App\Support\AnalyticsIps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Yönetim › Analitik ayarları: sayılmayan IP'ler (panele girenler, otomatik), istenmeyen IP'ler (elle) ve
 * bu IP'lerin analitikteki eski kayıtlarını silme.
 */
class AnalyticsSettingsController extends Controller
{
    public function index(Request $request)
    {
        $auto = AnalyticsIps::auto();
        $blocked = AnalyticsIps::blocked();

        return view('admin.analytics.settings', [
            'auto' => $auto,
            'blocked' => $blocked,
            'counts' => AnalyticsIps::counts(array_values(array_unique([...$auto, ...$blocked]))),
            'myIp' => $request->ip(),
        ]);
    }

    /** İstenmeyen IP'ler: kutuya yazılan liste kaydedilir (satır, virgül ya da boşlukla) */
    public function saveBlocked(Request $request)
    {
        $request->validate(['ips' => ['nullable', 'string', 'max:20000']]);
        [$valid, $invalid] = AnalyticsIps::parse((string) $request->input('ips'));
        if ($invalid) {
            return back()->withInput()->withErrors(['ips' => __('Geçersiz IP: :list', ['list' => implode(', ', array_slice($invalid, 0, 5))])]);
        }
        AnalyticsIps::saveBlocked($valid);

        return back()->with('success', __('İstenmeyen IP listesi kaydedildi (:count IP). Bu IP\'lerden gelen yeni ziyaretler sayılmayacak.', ['count' => count($valid)]));
    }

    /** Listelerdeki IP'lerin (ya da tek bir IP'nin) eski kayıtlarını analitikten siler */
    public function purge(Request $request)
    {
        $data = $request->validate(['ip' => ['nullable', 'ip']]);
        $ips = filled($data['ip'] ?? null) ? [$data['ip']] : AnalyticsIps::all();
        $deleted = AnalyticsIps::purge($ips);
        Cache::forever('analytics:version', (int) Cache::get('analytics:version', 0) + 1); // analitik özetleri yeniden hesaplansın

        return back()->with('success', $deleted
            ? __(':count kayıt analitikten silindi.', ['count' => number_format($deleted, 0, ',', '.')])
            : __('Silinecek kayıt yoktu.'));
    }
}
