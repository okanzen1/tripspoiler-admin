<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaqRequest;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\Faq;
use Illuminate\Http\Request;

/**
 * Sıkça sorulan sorular: genel, aktivitelere ve blog yazılarına bağlı SSS'ler tek yerde.
 */
class FaqController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        // Sekmeler: Anasayfa · Şehir sayfaları (Aktiviteler/Şehirler/Blog) · Aktivite detayı · Blog yazısı · Diğer sayfalar
        $scope = in_array($request->get('scope'), ['general', 'city', 'activity', 'blog', 'other'], true) ? $request->get('scope') : null;
        $cityPages = ['activities', 'cities', 'blog'];
        $cityId = $request->integer('city') ?: null;
        $cityScope = fn ($q) => $q->whereNull('activity_id')->whereNull('blog_id')->where(fn ($w) => $w->whereIn('page_key', $cityPages)->orWhere(fn ($c) => $c->whereNull('page_key')->whereNotNull('city_id')));
        $otherScope = fn ($q) => $q->whereNotNull('page_key')->whereNotIn('page_key', $cityPages);
        $activityId = $request->integer('activity') ?: null;
        $blogId = $request->integer('blog') ?: null;
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;

        $faqs = Faq::with(['activity', 'blog', 'city', 'page'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('question->en', 'like', "%{$search}%")->orWhere('answer->en', 'like', "%{$search}%")))
            ->when($scope === 'general', fn ($q) => $q->general())
            ->when($scope === 'activity', fn ($q) => $q->whereNotNull('activity_id'))
            ->when($scope === 'blog', fn ($q) => $q->whereNotNull('blog_id'))
            ->when($scope === 'city', $cityScope)
            ->when($scope === 'other', $otherScope)
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->when($activityId, fn ($q) => $q->where('activity_id', $activityId))
            ->when($blogId, fn ($q) => $q->where('blog_id', $blogId))
            ->when($status, fn ($q) => $q->where('active', $status === 'active'))
            ->orderByRaw('page_key IS NOT NULL, city_id IS NOT NULL, activity_id IS NOT NULL, blog_id IS NOT NULL')
            ->orderBy('page_key')
            ->orderBy('city_id')
            ->orderBy('activity_id')
            ->orderBy('blog_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'search' => $search,
            'scope' => $scope,
            'activityId' => $activityId,
            'blogId' => $blogId,
            'status' => $status,
            'activities' => Activity::whereHas('faqs')->orderBy('sort_order')->get(),
            'blogs' => \App\Models\Blog::whereHas('faqs')->orderByDesc('published_at')->get(),
            'cityId' => $cityId,
            'cities' => \App\Models\City::whereIn('id', Faq::whereNotNull('city_id')->select('city_id'))->orderBy('name')->get(),
            'counts' => [
                'general' => Faq::general()->count(),
                'city' => $cityScope(Faq::query())->count(),
                'activity' => Faq::whereNotNull('activity_id')->count(),
                'blog' => Faq::whereNotNull('blog_id')->count(),
                'other' => $otherScope(Faq::query())->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $faq = new Faq(['active' => true]);
        $faq->activity_id = $request->integer('activity') ?: null;
        $faq->blog_id = $request->integer('blog') ?: null;
        // "Aynı yere yeni soru": düzenlenen sorunun görüneceği yer hazır seçili gelir, tek adımda kaydedilir
        $like = $request->integer('like') ? Faq::find($request->integer('like')) : null;
        if ($like) {
            $faq->fill($like->only(['activity_id', 'blog_id', 'city_id', 'page_key']));
        }

        return view('admin.faqs.form', [...$this->formData($faq), 'copied' => (bool) $like, 'bulk' => $request->boolean('bulk')]);
    }

    public function store(FaqRequest $request)
    {
        if (filled($request->validated('bulk'))) {
            return $this->storeMany($request);
        }

        $faq = new Faq([...$request->owner(), 'active' => $request->validated('active')]);
        $this->fillText($faq, $request);
        $faq->sort_order = (int) $this->siblings($faq)->max('sort_order') + 1;
        $faq->save();

        // Eklenen sorunun sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('faqs.edit', $faq)->with('success', __('Soru eklendi. Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    public function edit(Faq $faq)
    {
        return view('admin.faqs.form', $this->formData($faq));
    }

    public function update(FaqRequest $request, Faq $faq)
    {
        $owner = $request->owner();
        $moved = $faq->activity_id !== $owner['activity_id'] || $faq->blog_id !== $owner['blog_id']
            || $faq->city_id !== $owner['city_id'] || $faq->page_key !== $owner['page_key'];
        $faq->fill([...$owner, 'active' => $request->validated('active')]);
        $this->fillText($faq, $request);

        // Başka yere taşındıysa oranın sonuna eklenir
        if ($moved) {
            $faq->sort_order = (int) $this->siblings($faq)->max('sort_order') + 1;
        }

        $faq->save();

        return redirect()->route('faqs.index')->with('success', __('Soru güncellendi.'));
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return back()->with('success', __('Soru silindi.'));
    }

    public function toggle(Request $request, Faq $faq)
    {
        $faq->update(['active' => !$faq->active]);
        $message = __($faq->active ? 'Soru yayına alındı.' : 'Soru yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $faq->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Sürükle-bırak sırası (resources/js/sortable-cards.js): aynı yerdeki soruların id'leri yeni sırayla.
     * Listede görünen soruların mevcut sıra numaraları yeni sıraya dağıtılır; filtre ya da sayfa yüzünden
     * görünmeyen sorular yerinde kalır.
     */
    public function reorder(Request $request)
    {
        $ids = array_values(array_unique(array_map('intval', $request->validate([
            'order' => ['required', 'array', 'min:1', 'max:200'],
            'order.*' => ['integer'],
        ])['order'])));
        $faqs = Faq::whereIn('id', $ids)->get()->keyBy('id');
        $first = $faqs->first();
        abort_if(!$first || $faqs->count() !== count($ids), 422);
        abort_if($faqs->contains(fn (Faq $faq) => $faq->activity_id !== $first->activity_id || $faq->blog_id !== $first->blog_id
            || $faq->city_id !== $first->city_id || $faq->page_key !== $first->page_key), 422, __('Sorular farklı yerlerde.'));

        // Aynı sıra numarası olanlar (eski kayıtlar) ayrışsın diye boşlukları da sırayla doldur
        $slots = $faqs->pluck('sort_order')->sort()->values();
        $slots = $slots->unique()->count() === $slots->count() ? $slots : collect(range((int) $slots->min(), (int) $slots->min() + count($ids) - 1));

        \Illuminate\Support\Facades\DB::transaction(function () use ($ids, $slots) {
            foreach ($ids as $i => $id) {
                Faq::whereKey($id)->update(['sort_order' => $slots[$i]]);
            }
        });

        \App\Support\ActivityLogger::write('reordered', 'faq', null, __('Sıkça sorulan sorular') . ' · ' . $this->placeName($first));

        return response()->json(['ok' => true, 'message' => __('Sıra kaydedildi.')]);
    }

    private function placeName(Faq $faq): string
    {
        return match (true) {
            $faq->activity_id !== null => $faq->activity?->name ?? __('Aktivite'),
            $faq->blog_id !== null => 'Blog · ' . ($faq->blog?->title ?? ''),
            $faq->city_id !== null => __('Şehir') . ' · ' . ($faq->city?->name ?? ''),
            $faq->page_key !== null => __('Sayfa') . ' · ' . ($faq->page?->name ?? $faq->page_key),
            default => __('Anasayfa'),
        };
    }

    /**
     * Soru ve cevap: sadece İngilizce yazılır, diğer diller korunur.
     */
    /**
     * "Toplu ekle": yapıştırılan sorular aynı yere, sırayla eklenir. Zamanlayıcı (cron) çalışıyorsa her biri
     * sıraya alınıp diğer dillere çevrilir (App\Jobs\TranslateModel).
     */
    private function storeMany(FaqRequest $request)
    {
        $pairs = \App\Support\FaqParser::parse($request->validated('bulk'));
        $owner = $request->owner();
        $sort = (int) $this->siblings(new Faq($owner))->max('sort_order');
        $faqs = \Illuminate\Support\Facades\DB::transaction(fn () => array_map(function ($pair) use ($owner, $request, &$sort) {
            $faq = new Faq([...$owner, 'active' => $request->validated('active')]);
            $faq->setTranslation('question', 'en', $pair['question']);
            $faq->setTranslation('answer', 'en', $pair['answer']);
            $faq->sort_order = ++$sort;
            $faq->save();

            return $faq;
        }, $pairs));

        $queued = \Illuminate\Support\Facades\Cache::get('scheduler:last_run', 0) > time() - 180;
        if ($queued) {
            foreach ($faqs as $faq) {
                \App\Jobs\TranslateModel::dispatch($faq);
            }
        }

        return redirect()->route('faqs.index')->with('success', $queued
            ? __(':count soru eklendi; arka planda sırayla diğer dillere çevriliyor.', ['count' => count($faqs)])
            : __(':count soru eklendi. Listede "Çeviri eksik" olanları açıp çevirebilirsin.', ['count' => count($faqs)]));
    }

    private function fillText(Faq $faq, FaqRequest $request): void
    {
        $faq->setTranslation('question', 'en', $request->validated('question'));
        $faq->setTranslation('answer', 'en', $request->validated('answer'));
    }

    /**
     * Aynı yerdeki (aynı aktivite / blog / genel) sorular.
     */
    private function siblings(Faq $faq)
    {
        return Faq::where('activity_id', $faq->activity_id)->where('blog_id', $faq->blog_id)
            ->where('city_id', $faq->city_id)->where('page_key', $faq->page_key);
    }

    private function formData(Faq $faq): array
    {
        return [
            'faq' => $faq,
            'target' => match (true) {
                $faq->activity_id !== null => "activity:{$faq->activity_id}",
                $faq->blog_id !== null => "blog:{$faq->blog_id}",
                $faq->page_key !== null && $faq->city_id !== null => "page:{$faq->page_key}@{$faq->city_id}",
                $faq->city_id !== null => "city:{$faq->city_id}",
                $faq->page_key !== null => "page:{$faq->page_key}",
                default => 'general',
            },
            'activities' => Activity::with('city')->orderBy('city_id')->orderBy('sort_order')->get()
                ->groupBy(fn (Activity $activity) => $activity->city->name),
            'blogs' => Blog::orderByDesc('published_at')->get(),
            'cities' => \App\Models\City::with('country')->withCount('activities')->where(fn ($q) => $q->where('active', true)->orHas('activities'))
                ->orderByDesc('activities_count')->orderBy('name')->get(),
            'pages' => \App\Models\Page::whereIn('key', array_keys(\App\Support\PageSections::DEFAULTS))->orderBy('sort_order')->get(),
        ];
    }
}
