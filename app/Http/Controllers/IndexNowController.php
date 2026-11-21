<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\IndexNow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Yönetim › IndexNow: yeni / değişen sayfaların Bing, Yandex… arama motorlarına bildirimi (App\Services\IndexNow).
 */
class IndexNowController extends Controller
{
    public function __construct(private IndexNow $indexNow) {}

    /** Sayfa türleri: adresteki bölüm → panelde görünen ad */
    public const TYPES = ['activities' => 'Aktivite', 'blog' => 'Blog', 'cities' => 'Şehir', 'attractions' => 'Yer', 'other' => 'Diğer'];

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $lang = preg_match('/^[a-z]{2}$/', (string) $request->get('lang')) ? $request->get('lang') : null;
        $type = array_key_exists((string) $request->get('type'), self::TYPES) ? $request->get('type') : null;
        $base = rtrim(config('site.url'), '/');
        $sections = array_keys(array_diff_key(self::TYPES, ['other' => true]));

        $urls = DB::table('indexnow_urls')
            ->when($search, fn ($q) => $q->where('url', 'like', '%' . addcslashes($search, '%_\\') . '%'))
            ->when($lang, fn ($q) => $q->where(fn ($q) => $q->where('url', $base . '/' . $lang)->orWhere('url', 'like', $base . '/' . $lang . '/%')))
            ->when($type && $type !== 'other', fn ($q) => $q->where('url', 'like', $base . '/%/' . $type . '/%'))
            ->when($type === 'other', fn ($q) => $q->where(function ($q) use ($base, $sections) {
                foreach ($sections as $section) {
                    $q->where('url', 'not like', $base . '/%/' . $section . '/%');
                }
            }))
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->paginate(50, ['url', 'submitted_at'])->withQueryString();

        return view('admin.indexnow.index', [
            'urls' => $urls,
            'search' => $search,
            'lang' => $lang,
            'type' => $type,
            'types' => self::TYPES,
            'languages' => \App\Models\Language::active()->ordered()->get(['code', 'native_name']),
            'keyOk' => Cache::remember('indexnow:key-ok', now()->addMinutes(10), fn () => IndexNow::keyFileOk()),
            'keyUrl' => IndexNow::keyUrl(),
            'known' => DB::table('indexnow_urls')->count(),
            'today' => DB::table('indexnow_urls')->where('submitted_at', '>=', now()->startOfDay())->count(),
            'last' => IndexNow::last(),
            'lastRun' => (int) Setting::get('indexnow_last_run'),
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
