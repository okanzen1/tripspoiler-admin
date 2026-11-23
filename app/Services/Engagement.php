<?php

namespace App\Services;

use App\Models\AffiliateClick;
use App\Models\PageClick;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sayfa davranışı: kalma süresi, kaydırma, sayfa içi tıklamalar (sıcaklık haritası) ve bunlardan çıkan öneriler.
 * Veriyi site yazar (tripspoiler: resources/js/track.js → /t/leave): visits.duration / scroll / viewport_h ve page_clicks.
 */
class Engagement
{
    /** Panelde iki görünüm: bilgisayar (tablet de, geniş ekran) ve telefon */
    public const DEVICES = ['desktop' => ['desktop', 'tablet'], 'mobile' => ['mobile']];

    /** Bu kadar saniyeden kısa kalan "hemen çıktı" sayılır */
    public const QUICK_EXIT = 10;

    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $device = 'desktop',
    ) {}

    private function devices(): array
    {
        return self::DEVICES[$this->device] ?? self::DEVICES['desktop'];
    }

    private function visits()
    {
        return Visit::query()->where('visitor_id', 'not like', 'demo_%')
            ->whereBetween('created_at', [$this->start, $this->end])
            ->whereIn('device', $this->devices());
    }

    private function clicks()
    {
        return PageClick::query()->whereBetween('created_at', [$this->start, $this->end])->whereIn('device', $this->devices());
    }

    /**
     * Sayfa karnesi: en çok görüntülenen sayfalar; her biri için ortalama kalma, kaydırma, hemen çıkma, partner tıklama oranı,
     * sinirli / boşa tıklama sayısı. Süre/kaydırma bilgisi olmayan eski kayıtlar ortalamaya girmez.
     */
    public function pages(int $limit = 25): Collection
    {
        $quick = self::QUICK_EXIT;
        $views = $this->visits()
            ->selectRaw("path, COUNT(*) as views, COUNT(DISTINCT COALESCE(ip_hash, visitor_id)) as visitors,
                AVG(duration) as duration, AVG(scroll) as scroll, COUNT(duration) as measured,
                SUM(CASE WHEN duration < {$quick} THEN 1 ELSE 0 END) as quick")
            ->groupBy('path')->orderByDesc('views')->limit($limit)->get();
        if ($views->isEmpty()) {
            return collect();
        }
        $paths = $views->pluck('path');

        $clicks = $this->clicks()->whereIn('path', $paths)
            ->selectRaw('path, COUNT(*) as total, SUM(rage) as rage, SUM(dead) as dead')->groupBy('path')->get()->keyBy('path');
        $partner = AffiliateClick::query()->whereBetween('created_at', [$this->start, $this->end])->whereIn('device', $this->devices())
            ->whereIn('path', $paths)->selectRaw('path, COUNT(DISTINCT COALESCE(ip_hash, visitor_id)) as clickers')->groupBy('path')->pluck('clickers', 'path');

        return $views->map(function ($row) use ($clicks, $partner) {
            $measured = (int) $row->measured;
            $page = (object) [
                'path' => $row->path,
                'views' => (int) $row->views,
                'duration' => $row->duration !== null ? (int) round($row->duration) : null,
                'scroll' => $row->scroll !== null ? (int) round($row->scroll) : null,
                'quick' => $measured ? (int) round($row->quick / $measured * 100) : null,
                'measured' => $measured,
                'clicks' => (int) ($clicks[$row->path]->total ?? 0),
                'rage' => (int) ($clicks[$row->path]->rage ?? 0),
                'dead' => (int) ($clicks[$row->path]->dead ?? 0),
                'ctr' => $row->visitors ? round(($partner[$row->path] ?? 0) / $row->visitors * 100, 1) : null,
            ];
            $page->flags = self::flags($page);

            return $page;
        });
    }

    /** Karnedeki kısa uyarılar (yeterli ölçüm varsa) */
    private static function flags(object $page): array
    {
        if ($page->measured < 5) {
            return [];
        }

        return array_values(array_filter([
            $page->quick !== null && $page->quick >= 60 ? ['Hemen çıkıyorlar', 'red'] : null,
            $page->scroll !== null && $page->scroll < 40 ? ['Aşağı inmiyorlar', 'amber'] : null,
            $page->rage >= 2 ? ['Sinirli tıklama', 'red'] : null,
            $page->dead >= 5 ? ['Boşa tıklama', 'amber'] : null,
            $page->duration !== null && $page->duration >= 60 && ($page->scroll ?? 0) >= 60 ? ['İlgiyle okunuyor', 'emerald'] : null,
        ]));
    }

    /** Sıcaklık haritası için seçilebilecek sayfalar (tıklaması olan ya da çok görüntülenen) */
    public function pageOptions(int $limit = 60): Collection
    {
        $clicks = $this->clicks()->selectRaw('path, COUNT(*) as total')->groupBy('path')->pluck('total', 'path');
        $views = $this->visits()->selectRaw('path, COUNT(*) as total')->groupBy('path')->orderByDesc('total')->limit($limit)->pluck('total', 'path');

        return $views->keys()->merge($clicks->keys())->unique()
            ->map(fn ($path) => (object) ['path' => $path, 'views' => (int) ($views[$path] ?? 0), 'clicks' => (int) ($clicks[$path] ?? 0)])
            ->sortByDesc(fn ($p) => [$p->clicks, $p->views])->take($limit)->values();
    }

    /**
     * Tek sayfanın haritası: tıklama noktaları, kaydırma dağılımı, en çok tıklananlar, sinirli / boşa tıklamalar, özet ve öneriler.
     */
    public function page(string $path): array
    {
        $points = $this->clicks()->where('path_hash', PageClick::hash($path))->where('path', $path)
            ->latest('id')->limit(5000)->get(['x', 'y', 'vw', 'ph', 'label', 'href', 'rage', 'dead']);

        $visits = $this->visits()->where('path', $path);
        $summary = (clone $visits)->selectRaw('COUNT(*) as views, COUNT(DISTINCT COALESCE(ip_hash, visitor_id)) as visitors,
            AVG(duration) as duration, AVG(scroll) as scroll, COUNT(duration) as measured, AVG(viewport_h) as fold,
            SUM(CASE WHEN duration < ' . self::QUICK_EXIT . ' THEN 1 ELSE 0 END) as quick')->first();
        // ortanca süre: birkaç çok uzun kalan (sekmeyi açık unutan) ortalamayı şişirmesin
        $durations = (clone $visits)->whereNotNull('duration')->orderBy('duration')->limit(20000)->pluck('duration');
        $scrolls = (clone $visits)->whereNotNull('scroll')->limit(20000)->pluck('scroll')->map(fn ($s) => (int) $s);

        // kaydırma haritası: sayfanın her %1'ine ziyaretçilerin yüzde kaçı indi
        $reach = [];
        $total = max(1, $scrolls->count());
        $counts = array_fill(0, 101, 0);
        foreach ($scrolls as $s) {
            $counts[max(0, min(100, $s))]++;
        }
        $running = 0;
        for ($p = 100; $p >= 0; $p--) {
            $running += $counts[$p];
            $reach[$p] = round($running / $total * 100, 1);
        }
        ksort($reach);

        $partnerClickers = AffiliateClick::query()->whereBetween('created_at', [$this->start, $this->end])->whereIn('device', $this->devices())
            ->where('path', $path)->distinct()->count(DB::raw('COALESCE(ip_hash, visitor_id)'));

        $group = fn (Collection $items) => $items
            ->groupBy(fn ($c) => trim(($c->label ?? '') . '|' . ($c->href ?? '')))
            ->map(fn ($g) => (object) ['label' => $g->first()->label, 'href' => $g->first()->href, 'total' => $g->count(),
                'y' => (int) $g->median('y'), 'ph' => (int) $g->median('ph'), 'partner' => self::isExternal($g->first()->href)])
            ->sortByDesc('total')->values();

        $elements = $group($points);
        $sum = max(1, $points->count());
        $elements->each(fn ($e) => $e->percent = round($e->total / $sum * 100, 1));

        $data = [
            'activity' => str_contains($path, '/activities/'),
            'views' => (int) $summary->views,
            'visitors' => (int) $summary->visitors,
            'measured' => (int) $summary->measured,
            'duration' => $durations->isNotEmpty() ? (int) $durations->median() : null,
            'duration_avg' => $summary->duration !== null ? (int) round($summary->duration) : null,
            'scroll' => $summary->scroll !== null ? (int) round($summary->scroll) : null,
            'quick' => $summary->measured ? (int) round($summary->quick / $summary->measured * 100) : null,
            // ziyaretçinin ekranı: harita sayfayı bu boyda gösterir (telefon 390 px, bilgisayar 1440 px genişlik)
            'fold' => max(480, min(1100, $summary->fold ? (int) round($summary->fold) : ($this->device === 'mobile' ? 740 : 820))),
            'width' => $this->device === 'mobile' ? 390 : 1440,
            'ctr' => $summary->visitors ? round($partnerClickers / $summary->visitors * 100, 1) : null,
            'partner_clickers' => $partnerClickers,
            'clicks' => $points->count(),
            'points' => $points->map(fn ($c) => [(int) $c->x, (int) $c->y, (int) $c->vw, (int) $c->ph, (int) $c->rage, (int) $c->dead])->values(),
            'reach' => $scrolls->isNotEmpty() ? $reach : [],
            'elements' => $elements->take(15),
            'rage' => $group($points->where('rage', true))->take(8),
            'dead' => $group($points->where('dead', true))->take(8),
        ];
        $data['insights'] = $this->insights($data);

        return $data;
    }

    /** Partner (dış site) bağlantısı mı */
    public static function isExternal(?string $href): bool
    {
        return (bool) preg_match('#^https?://#i', (string) $href);
    }

    /**
     * Sade dille ne yapmalı: rakamlardan çıkan öneriler. Az veri varken yanıltmasın diye ölçüm sayısına bakılır.
     *
     * @return list<array{tone: string, title: string, text: string}>
     */
    private function insights(array $d): array
    {
        $tips = [];
        if ($d['measured'] < 5) {
            return [['tone' => 'slate', 'title' => __('Veri toplanıyor'), 'text' => __('Bu sayfa için henüz yeterli ölçüm yok (:count). Birkaç gün sonra burada öneriler çıkar.', ['count' => $d['measured']])]];
        }

        // Partner düğmesine kadar inen oranı
        $partner = $d['elements']->firstWhere('partner', true);
        if ($partner && $d['reach'] && ($height = $this->typicalHeight($d['points']))) {
            $at = (int) min(100, round($partner->y / $height * 100));
            $seen = $d['reach'][$at] ?? null;
            if ($seen !== null && $seen < 60) {
                $tips[] = ['tone' => 'red', 'title' => __('Rezervasyon düğmesi çok aşağıda'),
                    'text' => __('Ziyaretçilerin sadece %:seen kadarı ":label" düğmesine kadar iniyor. Bu düğmeyi (ya da bir kopyasını) sayfanın üstüne taşı.', ['seen' => round($seen), 'label' => \Illuminate\Support\Str::limit($partner->label ?: __('partner'), 40)])];
            }
        } elseif (!$partner && $d['views'] >= 30 && $d['activity']) {
            $tips[] = ['tone' => 'amber', 'title' => __('Partner düğmesine tıklanmıyor'), 'text' => __('Bu dönemde bu sayfada partner bağlantısına hiç tıklanmadı. Düğmenin görünür ve dikkat çekici olduğunu kontrol et.')];
        }

        if ($d['quick'] !== null && $d['quick'] >= 50) {
            $tips[] = ['tone' => 'red', 'title' => __('Gelenlerin çoğu hemen çıkıyor'),
                'text' => __('Gelenlerin %:p kadarı :s saniyeden önce çıkıyor. Sayfanın ilk ekranı (başlık, resim, ilk cümle) aradığını hemen göstermeli.', ['p' => $d['quick'], 's' => self::QUICK_EXIT])];
        }
        if ($d['scroll'] !== null && $d['scroll'] < 45) {
            $tips[] = ['tone' => 'amber', 'title' => __('Aşağı inen az'),
                'text' => __('Ortalama ziyaretçi sayfanın sadece %:p kadarını görüyor. En önemli bilgileri ve düğmeleri üst yarıya al.', ['p' => $d['scroll']])];
        }
        if ($rage = $d['rage']->first()) {
            $tips[] = ['tone' => 'red', 'title' => __('Sinirli tıklama var'),
                'text' => __('":label" üzerine art arda tıklanmış (:count kez). Muhtemelen çalışmıyor ya da yavaş açılıyor sanıyorlar: bir kontrol et.', ['label' => \Illuminate\Support\Str::limit($rage->label ?: __('isimsiz öğe'), 40), 'count' => $rage->total])];
        }
        if (($dead = $d['dead']->first()) && $dead->total >= 3) {
            $tips[] = ['tone' => 'amber', 'title' => __('Tıklanamayan yere tıklıyorlar'),
                'text' => __('":label" bir bağlantı değil ama :count kez tıklanmış. İnsanlar orada bir şey açılmasını bekliyor: bağlantı yapmayı düşün.', ['label' => \Illuminate\Support\Str::limit($dead->label ?: __('isimsiz öğe'), 40), 'count' => $dead->total])];
        }
        if ($d['duration'] !== null && $d['duration'] >= 60 && ($d['scroll'] ?? 0) >= 60) {
            $tips[] = ['tone' => 'emerald', 'title' => __('İlgiyle okunuyor'),
                'text' => __('Ziyaretçiler ortalama :t kalıp sayfanın çoğunu okuyor. Bu sayfaya daha çok trafik getirmek (iç bağlantı, paylaşım) iyi sonuç verir.', ['t' => self::duration($d['duration'])])];
        }
        if ($d['ctr'] !== null && $d['ctr'] >= 8) {
            $tips[] = ['tone' => 'emerald', 'title' => __('Para kazandıran sayfa'),
                'text' => __('Gelenlerin %:p kadarı partnere gidiyor. Bu sayfayı ana sayfada, ilgili bloglarda ve şehir sayfasında öne çıkar.', ['p' => number_format($d['ctr'], 1, ',', '.')])];
        }

        return $tips ?: [['tone' => 'emerald', 'title' => __('Belirgin bir sorun yok'), 'text' => __('Bu sayfada göze batan bir sorun görünmüyor.')]];
    }

    /** Tıklama kayıtlarındaki tipik sayfa boyu */
    private function typicalHeight(Collection $points): ?int
    {
        $heights = $points->pluck(3)->filter();

        return $heights->isNotEmpty() ? (int) $heights->median() : null;
    }

    /** 75 → "1 dk 15 sn" */
    public static function duration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        if ($seconds < 60) {
            return __(':s sn', ['s' => $seconds]);
        }
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m >= 60) {
            return __(':h sa :m dk', ['h' => intdiv($m, 60), 'm' => $m % 60]);
        }

        return $s ? __(':m dk :s sn', ['m' => $m, 's' => $s]) : __(':m dk', ['m' => $m]);
    }
}
