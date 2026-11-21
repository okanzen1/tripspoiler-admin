<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Attraction;
use App\Models\Blog;
use App\Models\City;
use App\Models\Setting;
use App\Services\SearchConsole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Yönetim › Search Console: Google'daki görünürlük (son 28 gün). Sekmeler:
 *  - fırsatlar: ilk 10'da görünüp az tıklanan aramalar (başlık / açıklama düzeltilecek)
 *  - yakın: 8–20. sıradaki sayfalar (biraz emekle ilk sayfaya çıkabilir)
 *  - sayfalar, aramalar: tam liste
 */
class SearchConsoleController extends Controller
{
    public const TABS = ['firsatlar' => 'Fırsatlar', 'yakin' => 'İlk sayfaya yakın', 'sayfalar' => 'Sayfalar', 'aramalar' => 'Aramalar'];

    /** Fırsat sayılması için en az gösterim ve en yüksek tıklanma oranı */
    public const MIN_IMPRESSIONS = 20;

    public const LOW_CTR = 0.02;

    /** @var array<string, true> adresi değişmiş (yönlendirilen) sayfalar */
    private array $moved = [];

    public function __construct(private SearchConsole $gsc) {}

    public function index(Request $request)
    {
        $tab = array_key_exists((string) $request->get('tab'), self::TABS) ? $request->get('tab') : 'firsatlar';
        $search = trim((string) $request->get('search'));
        $like = '%' . addcslashes($search, '%_\\') . '%';

        $rows = match ($tab) {
            'firsatlar' => DB::table('search_console_rows')->whereNotNull('query')
                ->where('impressions', '>=', self::MIN_IMPRESSIONS)->where('position', '<=', 10)->where('ctr', '<', self::LOW_CTR)
                ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('query', 'like', $like)->orWhere('page', 'like', $like)))
                ->orderByDesc('impressions'),
            'yakin' => DB::table('search_console_rows')->whereNull('query')->whereBetween('position', [8, 20])
                ->when($search, fn ($q) => $q->where('page', 'like', $like))
                ->orderByDesc('impressions'),
            'sayfalar' => DB::table('search_console_rows')->whereNull('query')
                ->when($search, fn ($q) => $q->where('page', 'like', $like))
                ->orderByDesc('clicks')->orderByDesc('impressions'),
            'aramalar' => DB::table('search_console_rows')->whereNotNull('query')
                ->when($search, fn ($q) => $q->where('query', 'like', $like))
                ->groupBy('query')
                ->selectRaw('query, SUM(clicks) as clicks, SUM(impressions) as impressions, '
                    . 'CASE WHEN SUM(impressions) > 0 THEN SUM(clicks) * 1.0 / SUM(impressions) ELSE 0 END as ctr, '
                    . 'CASE WHEN SUM(impressions) > 0 THEN SUM(position * impressions) / SUM(impressions) ELSE 0 END as position, '
                    . 'COUNT(*) as pages, NULL as page')
                ->orderByDesc('clicks')->orderByDesc('impressions'),
        };
        $rows = $rows->paginate(50)->withQueryString();

        $sites = [];
        $sitesError = null;
        if (SearchConsole::connected()) {
            try {
                $sites = $this->gsc->sites();
            } catch (\Throwable $e) {
                $sitesError = $e->getMessage();
            }
        }

        return view('admin.search-console.index', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'search' => $search,
            'rows' => $rows,
            'edit' => $this->editLinks($rows->getCollection()->pluck('page')->filter()->unique()->all()),
            'moved' => $this->moved,
            'connected' => SearchConsole::connected(),
            'serviceEmail' => SearchConsole::serviceEmail(),
            'site' => SearchConsole::site(),
            'sites' => $sites,
            'sitesError' => $sitesError,
            'totals' => SearchConsole::totals(),
            'syncedAt' => (int) Setting::get('gsc_synced_at'),
            'error' => Setting::get('gsc_error'),
            'counts' => [
                'firsatlar' => DB::table('search_console_rows')->whereNotNull('query')->where('impressions', '>=', self::MIN_IMPRESSIONS)
                    ->where('position', '<=', 10)->where('ctr', '<', self::LOW_CTR)->count(),
                'yakin' => DB::table('search_console_rows')->whereNull('query')->whereBetween('position', [8, 20])->count(),
            ],
            'lowCtr' => self::LOW_CTR,
            'minImpressions' => self::MIN_IMPRESSIONS,
        ]);
    }

    /** Hizmet hesabı JSON anahtarı (kullanıcı yapıştırır; şifreli saklanır) */
    public function saveCredentials(Request $request)
    {
        $request->validate(['credentials' => ['required', 'string', 'max:20000']]);
        try {
            SearchConsole::saveCredentials(trim((string) $request->input('credentials')));
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['credentials' => $e->getMessage()]);
        }

        return back()->with('success', __('Anahtar kaydedildi. Şimdi aşağıda mülkü seç.'));
    }

    public function saveSite(Request $request)
    {
        $data = $request->validate(['site' => ['required', 'string', 'max:255']]);
        Setting::put('gsc_site', $data['site']);
        $result = $this->gsc->sync();

        return back()->with($result['error'] ? 'error' : 'success', $result['error']
            ?? __('Mülk kaydedildi, son 28 günün verisi çekildi.'));
    }

    public function sync()
    {
        $result = $this->gsc->sync();

        return back()->with($result['error'] ? 'error' : 'success', $result['error'] ?? __('Veriler güncellendi.'));
    }

    public function disconnect()
    {
        $this->gsc->disconnect();

        return back()->with('success', __('Search Console bağlantısı kaldırıldı.'));
    }

    /**
     * Sitedeki adres → paneldeki düzenleme sayfası (/{dil}/blog|activities|attractions|cities/{slug}).
     *
     * @param  list<string>  $pages
     * @return array<string, string>
     */
    private function editLinks(array $pages): array
    {
        $links = [];
        $this->moved = [];
        foreach ($pages as $page) {
            $path = '/' . trim(rawurldecode((string) parse_url($page, PHP_URL_PATH)), '/');
            // adresi değişmiş sayfa: Google eski adresi biliyor, yönlendirme kaydından yeni adrese gidilir
            if ($to = \App\Models\Redirect::where('from_path', $path)->value('to_path')) {
                $this->moved[$page] = true;
                $path = $to;
            }
            $path = trim($path, '/');
            if (!preg_match('#^([a-z]{2})/(blog|activities|attractions|cities)/([^/]+)$#', $path, $m)) {
                continue;
            }
            [, $locale, $section, $slug] = $m;
            $find = fn ($model) => $model::query()->where(fn ($q) => $q->where('slug', $slug)
                ->when(in_array($locale, Blog::SLUG_LOCALES, true), fn ($q) => $q->orWhere("slugs->{$locale}", $slug)))->first();
            $links[$page] = match ($section) {
                'blog' => ($m = $find(Blog::class)) ? route('blogs.edit', $m) : null,
                'activities' => ($m = $find(Activity::class)) ? route('activities.edit', $m) : null,
                'attractions' => ($m = Attraction::where('slug', $slug)->first()) ? route('attractions.edit', $m) : null,
                'cities' => ($m = City::where('slug', $slug)->first()) ? route('city-guides.show', $m) : null,
            };
        }

        return array_filter($links);
    }
}
