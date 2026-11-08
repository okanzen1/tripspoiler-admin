<?php

namespace App\Services;

use App\Models\AffiliateClick;
use App\Models\Partner;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Seçilen dönem için analitik özetleri. Tüm hesaplar visits + affiliate_clicks
 * tablolarından canlı yapılır.
 */
class Analytics
{
    public const RANGES = [7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 90 gün'];

    private ?array $partnerNames = null;

    public readonly CarbonImmutable $start;
    public readonly CarbonImmutable $end;
    private readonly CarbonImmutable $previousStart;
    private readonly CarbonImmutable $previousEnd;

    public function __construct(public readonly int $days)
    {
        $this->end = CarbonImmutable::now()->endOfDay();
        $this->start = $this->end->subDays($days - 1)->startOfDay();
        $this->previousEnd = $this->start->subSecond();
        $this->previousStart = $this->start->subDays($days);
    }

    /**
     * KPI'lar: değer + önceki döneme göre değişim (%).
     *
     * @return array<string, array{value: float|int, previous: float|int, change: ?float}>
     */
    public function kpis(): array
    {
        $current = $this->totals($this->start, $this->end);
        $previous = $this->totals($this->previousStart, $this->previousEnd);

        return collect($current)->map(fn ($value, $key) => [
            'value' => $value,
            'previous' => $previous[$key],
            'change' => $previous[$key] > 0 ? round(($value - $previous[$key]) / $previous[$key] * 100, 1) : null,
        ])->all();
    }

    /**
     * Günlük seriler (boş günler 0).
     *
     * @return array{labels: list<string>, visitors: list<int>, views: list<int>, clicks: list<int>, ctr: list<float>}
     */
    public function daily(): array
    {
        $visits = $this->visits($this->start, $this->end)
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT visitor_id) as visitors, COUNT(*) as views')
            ->groupBy('day')->get()->keyBy('day');

        $clicks = $this->clicks($this->start, $this->end)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as clicks, COUNT(DISTINCT visitor_id) as clickers')
            ->groupBy('day')->get()->keyBy('day');

        $series = ['labels' => [], 'visitors' => [], 'views' => [], 'clicks' => [], 'ctr' => []];

        foreach (CarbonPeriod::create($this->start, $this->end->startOfDay()) as $date) {
            $key = $date->toDateString();
            $visitors = (int) ($visits[$key]->visitors ?? 0);
            $clickers = (int) ($clicks[$key]->clickers ?? 0);

            $series['labels'][] = $date->locale(app()->getLocale())->translatedFormat('d M');
            $series['visitors'][] = $visitors;
            $series['views'][] = (int) ($visits[$key]->views ?? 0);
            $series['clicks'][] = (int) ($clicks[$key]->clicks ?? 0);
            $series['ctr'][] = $visitors > 0 ? round($clickers / $visitors * 100, 1) : 0;
        }

        return $series;
    }

    /**
     * En çok tıklama getiren sayfalar (görüntüleme ve tıklama oranıyla).
     */
    public function topPages(int $limit = 8): Collection
    {
        $views = $this->visits($this->start, $this->end)
            ->selectRaw('path, MAX(page_type) as page_type, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path')->get()->keyBy('path');

        $clicks = $this->clicks($this->start, $this->end)
            ->selectRaw('path, COUNT(*) as clicks, COUNT(DISTINCT visitor_id) as clickers')
            ->groupBy('path')->get()->keyBy('path');

        return $views->keys()->merge($clicks->keys())->unique()
            ->map(function (string $path) use ($views, $clicks) {
                $visitors = (int) ($views[$path]->visitors ?? 0);
                $clickers = (int) ($clicks[$path]->clickers ?? 0);

                return (object) [
                    'path' => $path,
                    'type' => $views[$path]->page_type ?? null,
                    'views' => (int) ($views[$path]->views ?? 0),
                    'clicks' => (int) ($clicks[$path]->clicks ?? 0),
                    'ctr' => $visitors > 0 ? round($clickers / $visitors * 100, 1) : null,
                ];
            })
            ->sortByDesc(fn ($row) => [$row->clicks, $row->views])
            ->take($limit)
            ->values();
    }

    /**
     * Tıklamaların partnerlere dağılımı.
     */
    public function partners(): Collection
    {
        return $this->share(
            $this->clicks($this->start, $this->end)->selectRaw('partner as label, COUNT(*) as total')->groupBy('partner'),
            // Partner adı Partnerler tablosundan; kayıtlı değilse koddan türetilir
            fn ($label) => $this->partnerNames()[$label] ?? Str::headline($label),
        );
    }

    /**
     * Trafik kaynakları: her oturumun giriş sayfasındaki utm_source, yoksa referrer.
     * Sonraki sayfalarda kaynak boş olduğu için sadece giriş sayfaları sayılır.
     */
    public function sources(): Collection
    {
        $landings = $this->visits($this->start, $this->end)->selectRaw('MIN(id)')->groupBy('session_id');

        return $this->share(
            Visit::query()->whereIn('id', $landings)
                ->selectRaw('COALESCE(utm_source, referrer_host) as label, COUNT(*) as total')
                ->groupBy('label'),
            fn ($label) => $label ?: __('Doğrudan'),
            limit: 6,
        );
    }

    public function devices(): Collection
    {
        $names = ['desktop' => __('Masaüstü'), 'mobile' => __('Mobil'), 'tablet' => __('Tablet')];

        return $this->share(
            $this->visits($this->start, $this->end)->selectRaw('device as label, COUNT(DISTINCT visitor_id) as total')->groupBy('device'),
            fn ($label) => $names[$label] ?? __('Bilinmiyor'),
        );
    }

    public function countries(): Collection
    {
        return $this->share(
            $this->visits($this->start, $this->end)->selectRaw('country as label, COUNT(DISTINCT visitor_id) as total')->groupBy('country'),
            fn ($label) => $label ? (\Locale::getDisplayRegion("-{$label}", app()->getLocale()) ?: $label) : __('Bilinmiyor'),
            limit: 6,
        );
    }

    private function partnerNames(): array
    {
        return $this->partnerNames ??= Partner::pluck('name', 'slug')->all();
    }

    /**
     * Canlı: son 5 dakikada sitede olanlar, şu an açık sayfalar ve son ziyaretler (IP, ülke/şehir, kaynak, cihaz).
     *
     * @return array{active: int, today: int, pages: Collection, recent: Collection}
     */
    public static function live(int $recent = 30): array
    {
        $since = now()->subMinutes(5);
        $real = fn () => Visit::query()->where('visitor_id', 'not like', 'demo_%');

        return [
            'active' => $real()->where('created_at', '>=', $since)->distinct('visitor_id')->count('visitor_id'),
            'today' => $real()->where('created_at', '>=', today())->distinct('visitor_id')->count('visitor_id'),
            'pages' => $real()->where('created_at', '>=', $since)
                ->selectRaw('path, COUNT(DISTINCT visitor_id) as total')->groupBy('path')->orderByDesc('total')->limit(6)->get(),
            'recent' => $real()->latest('id')->limit($recent)->get(),
        ];
    }

    /** Ülke kodu → bayrak emojisi */
    public static function flag(?string $country): string
    {
        return $country && preg_match('/^[A-Z]{2}$/', $country)
            ? mb_chr(0x1F1E6 + ord($country[0]) - 65) . mb_chr(0x1F1E6 + ord($country[1]) - 65)
            : '🌐';
    }

    public function hasDemoData(): bool
    {
        return Visit::where('visitor_id', 'like', 'demo_%')->exists();
    }

    public function hasAnyData(): bool
    {
        return Visit::exists() || AffiliateClick::exists();
    }

    /**
     * @return array{visitors: int, views: int, clicks: int, ctr: float}
     */
    private function totals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $visits = $this->visits($from, $to)
            ->selectRaw('COUNT(DISTINCT visitor_id) as visitors, COUNT(*) as views')->first();
        $clicks = $this->clicks($from, $to)
            ->selectRaw('COUNT(*) as clicks, COUNT(DISTINCT visitor_id) as clickers')->first();

        $visitors = (int) $visits->visitors;

        return [
            'visitors' => $visitors,
            'views' => (int) $visits->views,
            'clicks' => (int) $clicks->clicks,
            'ctr' => $visitors > 0 ? round($clicks->clickers / $visitors * 100, 1) : 0.0,
        ];
    }

    /**
     * Gruplanmış sayımları yüzde payıyla döndürür; limit aşılırsa kalanlar "Diğer"e katlanır.
     */
    private function share(Builder $query, callable $label, int $limit = 5): Collection
    {
        $rows = $query->orderByDesc('total')->get();
        $sum = max(1, $rows->sum('total'));

        $items = $rows->take($limit)->map(fn ($row) => (object) ['label' => $label($row->label), 'total' => (int) $row->total]);

        if ($rows->count() > $limit) {
            $items->push((object) ['label' => __('Diğer'), 'total' => (int) $rows->slice($limit)->sum('total')]);
        }

        return $items->each(fn ($item) => $item->percent = round($item->total / $sum * 100, 1));
    }

    private function visits(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Visit::query()->whereBetween('created_at', [$from, $to]);
    }

    private function clicks(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return AffiliateClick::query()->whereBetween('created_at', [$from, $to]);
    }
}
