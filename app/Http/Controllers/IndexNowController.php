<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\IndexNow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Yönetim › IndexNow: yeni / değişen sayfaların Bing, Yandex… arama motorlarına bildirimi (App\Services\IndexNow).
 */
class IndexNowController extends Controller
{
    public function __construct(private IndexNow $indexNow) {}

    public function index()
    {
        return view('admin.indexnow.index', [
            'keyOk' => Cache::remember('indexnow:key-ok', now()->addMinutes(10), fn () => IndexNow::keyFileOk()),
            'keyUrl' => IndexNow::keyUrl(),
            'known' => DB::table('indexnow_urls')->count(),
            'today' => DB::table('indexnow_urls')->where('submitted_at', '>=', now()->startOfDay())->count(),
            'last' => IndexNow::last(),
            'lastRun' => (int) Setting::get('indexnow_last_run'),
            'recent' => DB::table('indexnow_urls')->orderByDesc('submitted_at')->orderByDesc('id')->limit(15)->get(['url', 'submitted_at']),
        ]);
    }

    /** Şimdi kontrol et: haritadaki yeni / değişen adresleri hemen gönderir */
    public function sync()
    {
        Cache::forget('indexnow:key-ok');
        $result = $this->indexNow->sync();

        return back()->with($result['error'] ? 'error' : 'success', $result['error']
            ?? ($result['sent']
                ? __(':count adres arama motorlarına bildirildi.', ['count' => number_format($result['sent'], 0, ',', '.')])
                : __('Yeni ya da değişen sayfa yok; hepsi zaten bildirildi.')));
    }

    /** Hepsini yeniden gönder: geçmişi siler ve haritadaki bütün adresleri tekrar bildirir */
    public function resend()
    {
        $this->indexNow->reset();

        return $this->sync();
    }
}
