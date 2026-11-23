<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlogRequest;
use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Visit;
use App\Services\ImageUploader;
use App\Support\FaqSync;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;
        $cityId = $request->get('city');
        $cover = in_array($request->get('cover'), ['yes', 'no'], true) ? $request->get('cover') : null;
        $kind = array_key_exists((string) $request->get('kind'), Blog::KINDS) ? $request->get('kind') : null;

        $blogs = Blog::with(['city.country', 'cities.country'])->withCount('activities')
            ->withExists(['pinterestPins as on_pinterest' => fn ($q) => $q->whereNotNull('pin_id')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('title->en', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('active', $status === 'active'))
            ->when($cityId === 'none', fn ($q) => $q->doesntHave('cities'))
            ->when(is_numeric($cityId), fn ($q) => $q->whereHas('cities', fn ($c) => $c->whereKey((int) $cityId)))
            ->when($cover, fn ($q) => $cover === 'yes' ? $q->whereNotNull('cover_path') : $q->whereNull('cover_path'))
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            // sitedeki sırayla (şehir şehir; sıralanmamış yeni yazılar başta, sonra sürüklenen sıra, sonra en yeni)
            ->orderBy('city_id')
            ->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Son 30 günün okunması (analitikteki sayfa görüntülemeleri)
        $views = Visit::where('page_type', 'blog')->whereIn('page_id', $blogs->pluck('id'))
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('page_id, COUNT(*) as total')->groupBy('page_id')->pluck('total', 'page_id');

        // Şehir içindeki sıra numarası (filtre olsa da gerçek sıra)
        $positions = [];
        foreach (Blog::query()->orderBy('city_id')->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')->get(['id', 'city_id'])->groupBy(fn ($b) => (string) $b->city_id) as $group) {
            foreach ($group->values() as $i => $item) {
                $positions[$item->id] = $i + 1;
            }
        }

        return view('admin.blogs.index', [
            'blogs' => $blogs,
            'positions' => $positions,
            'views' => $views,
            'search' => $search,
            'status' => $status,
            'cityId' => $cityId,
            'cover' => $cover,
            'kind' => $kind,
            'kindCounts' => Blog::selectRaw('kind, COUNT(*) as total')->groupBy('kind')->pluck('total', 'kind'),
            // yazı sayısına göre (çok yazısı olan şehir başta); "Genel" en sonda
            'filterCities' => City::with('country')->whereHas('blogs')->withCount('blogs')->orderByDesc('blogs_count')->orderBy('name')->get(),
            'generalCount' => Blog::doesntHave('cities')->count(),
            'noCoverCount' => Blog::whereNull('cover_path')->count(),
            'activeCount' => Blog::active()->count(),
            'totalCount' => Blog::count(),
        ]);
    }

    public function create()
    {
        return view('admin.blogs.form', $this->formData(new Blog(['active' => false, 'published_at' => now()])));
    }

    public function store(BlogRequest $request)
    {
        $blog = DB::transaction(function () use ($request) {
            $blog = new Blog();
            $this->fill($blog, $request);
            $blog->save();
            $this->syncActivities($blog, $request);
            $blog->cities()->sync($request->validated('city_ids') ?? []);
            FaqSync::sync($blog->faqs(), $request->validated('faqs') ?? []);

            return $blog;
        });

        // Eklenen kaydın sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('blogs.edit', $blog)->with('success', __('":name" eklendi.', ['name' => $blog->title]) . ' ' . __('Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    /**
     * Hazır yazı yapıştır: Claude / ChatGPT'nin verdiği HTML'den taslak yazı (resimler panelden eklenir).
     */
    public function importForm()
    {
        return view('admin.blogs.import', [
            'cities' => City::with('country')->whereHas('country', fn ($q) => $q->where('active', true))->orderByDesc('active')->orderBy('name')->get(),
            'prompt' => \App\Support\ContentImport::blogPrompt(),
        ]);
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'html' => ['required', 'string', 'max:300000'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ], [], ['html' => __('yazı')]);

        $posts = array_filter(\App\Support\ContentImport::blogs($data['html']), fn ($p) => $p['title'] && $p['content']);
        if (!$posts) {
            return back()->withInput()->withErrors(['html' => __('Yazıda başlık ya da metin bulunamadı. Talimatı kopyalayıp yeniden ürettir; her yazıda <title> ve <article> olmalı.')]);
        }

        $added = [];
        $skipped = [];
        // Aynı yazı ikinci kez yapıştırılırsa eklenmez (İngilizce başlık, büyük/küçük harf fark etmez)
        $titles = Blog::all()->map(fn (Blog $b) => mb_strtolower((string) $b->getTranslation('title', 'en', false)))->flip();
        DB::transaction(function () use ($posts, $data, &$added, &$skipped, $titles) {
            foreach ($posts as $parsed) {
                $key = mb_strtolower($parsed['title']);
                if ($titles->has($key)) {
                    $skipped[] = $parsed['title'];
                    continue;
                }
                $titles->put($key, true);
                $slug = \Illuminate\Support\Str::slug($parsed['slug'] ?: $parsed['title']) ?: 'post';
                for ($base = $slug, $i = 2; Blog::where('slug', $slug)->exists(); $i++) {
                    $slug = "{$base}-{$i}";
                }

                // Tarih boş kalmasın: ilk yazı bugün, sonrakiler 3'er gün geriye (hepsi aynı gün yazılmış görünmesin)
                // tür: yazıdaki <meta name="kind"> (guide / news); yoksa rehber
                $blog = new Blog(['slug' => $slug, 'city_id' => $data['city_id'] ?? null, 'kind' => $parsed['kind'] ?? 'guide', 'active' => false,
                    'published_at' => now()->startOfDay()->subDays(3 * count($added))]);
                foreach (['title', 'excerpt', 'content', 'meta_title', 'meta_description'] as $field) {
                    filled($parsed[$field]) && $blog->setTranslation($field, 'en', $parsed[$field]);
                }
                $parsed['tags'] && $blog->setTranslation('tags', 'en', $parsed['tags']);
                $blog->save();
                $blog->cities()->sync(array_filter([$data['city_id'] ?? null]));
                FaqSync::sync($blog->faqs(), $parsed['faqs']);
                $added[] = $blog;
            }
        });

        $message = $added
            ? __(':count yazı taslak olarak eklendi. Kapak resimlerini ekleyip yayına al.', ['count' => count($added)])
            : __('Yeni yazı eklenmedi.');
        if ($skipped) {
            $message .= ' ' . __('Zaten olduğu için atlananlar: :titles', ['titles' => implode(', ', $skipped)]);
        }

        // Tek yazıysa direkt o yazıya, çoksa taslak listesine
        return count($added) === 1
            ? redirect()->route('blogs.edit', $added[0])->with('success', $message)
            : redirect()->route($added ? 'blogs.index' : 'blogs.import', $added ? ['status' => 'passive', 'cover' => 'no'] : [])->with('success', $message);
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.form', $this->formData($blog->load(['activities', 'faqs', 'cities'])));
    }

    public function update(BlogRequest $request, Blog $blog)
    {
        DB::transaction(function () use ($request, $blog) {
            $this->fill($blog, $request);
            $blog->save();
            $this->syncActivities($blog, $request);
            $blog->cities()->sync($request->validated('city_ids') ?? []);
            FaqSync::sync($blog->faqs(), $request->validated('faqs') ?? []);
        });

        return redirect()->route('blogs.edit', $blog)->with('success', __('Değişiklikler kaydedildi.'));
    }

    public function destroy(Blog $blog)
    {
        $this->uploader->delete($blog->cover_path);
        $blog->delete();

        return redirect()->route('blogs.index')->with('success', __('":name" silindi.', ['name' => $blog->title]));
    }

    /**
     * Sıralama: şehir şehir yayındaki yazılar; sürükle-bırak ile sitedeki sıra (blog sayfası, öne çıkan = ilk).
     */
    public function order(Request $request)
    {
        $cities = City::with('country')->whereHas('blogs')->withCount('blogs')->orderByDesc('blogs_count')->orderBy('name')->get();
        $city = $cities->firstWhere('id', $request->integer('city')) ?? $cities->first();

        return view('admin.blogs.order', [
            'cities' => $cities,
            'city' => $city,
            'blogs' => $city ? Blog::whereHas('cities', fn ($q) => $q->whereKey($city->id))->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')->get() : collect(),
        ]);
    }

    /**
     * Listede sürükle-bırak (resources/js/sortable-cards.js): aynı şehirdeki yazıların id'leri yeni sırayla.
     * Şehirdeki bütün yazılar sitedeki sırayla 1, 2, 3… numaralanır; görünenlerin yerleri yeni sıraya dağıtılır,
     * filtre / sayfa yüzünden görünmeyenler yerinde kalır.
     */
    public function reorder(Request $request)
    {
        $ids = array_values(array_unique(array_map('intval', $request->validate([
            'order' => ['required', 'array', 'min:1', 'max:200'],
            'order.*' => ['integer'],
        ])['order'])));
        $moved = Blog::whereIn('id', $ids)->get(['id', 'city_id']);
        abort_if($moved->count() !== count($ids) || $moved->pluck('city_id')->unique()->count() !== 1, 422, __('Yazılar farklı şehirlerde.'));

        \Illuminate\Support\Facades\DB::transaction(function () use ($ids, $moved) {
            $cityId = $moved->first()->city_id;
            $all = Blog::query()->when($cityId === null, fn ($q) => $q->whereNull('city_id'), fn ($q) => $q->where('city_id', $cityId))
                ->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')
                ->pluck('id')->values();
            $slots = $all->flip()->only($ids)->sort()->values();
            $order = $all->all();
            foreach ($ids as $i => $id) {
                $order[$slots[$i]] = $id;
            }
            foreach ($order as $position => $id) {
                Blog::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function saveOrder(Request $request)
    {
        $data = $request->validate(['order' => ['array'], 'order.*' => ['integer']]);
        foreach (array_values($data['order'] ?? []) as $i => $id) {
            Blog::whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return response()->json(['message' => __('Sıra kaydedildi. Blog sayfasında hemen görünür.')]);
    }

    public function toggle(Request $request, Blog $blog)
    {
        $blog->update(['active' => !$blog->active]);
        $message = __($blog->active ? '":name" yayına alındı.' : '":name" yayından kaldırıldı.', ['name' => $blog->title]);

        return $request->expectsJson()
            ? response()->json(['active' => $blog->active, 'message' => $message])
            : back()->with('success', $message);
    }

    private function fill(Blog $blog, BlogRequest $request): void
    {
        $data = $request->validated();

        $blog->fill([
            'slug' => $data['slug'],
            // ana şehir: seçilen ilk şehir (genel yazıda boş)
            'city_id' => $data['city_ids'][0] ?? null,
            'kind' => $data['kind'],
            'active' => $data['active'],
            // Yayına alınırken tarih boşsa şimdi yazılır
            'published_at' => $data['published_at'] ?? ($data['active'] ? ($blog->published_at ?? now()) : null),
        ]);

        // Çok dilli alanlar: sadece İngilizce yazılır, diğer diller korunur
        $text = [
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => HtmlSanitizer::clean($data['content'] ?? null),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'tags' => $request->tags() ?: null,
        ];
        foreach ($text as $field => $value) {
            filled($value) ? $blog->setTranslation($field, 'en', $value) : $blog->forgetTranslation($field, 'en');
        }

        if ($request->hasFile('cover')) {
            $blog->cover_path = $this->uploader->cover($request->file('cover'), replace: $blog->cover_path);
        } elseif ($request->boolean('remove_cover')) {
            $this->uploader->delete($blog->cover_path);
            $blog->cover_path = null;
        }

        if ($request->has('cover_focus')) {
            $blog->cover_focus = (int) $request->input('cover_focus');
        }
    }

    private function syncActivities(Blog $blog, BlogRequest $request): void
    {
        $ids = collect($request->validated('activity_ids') ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $blog->activities()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
    }

    private function formData(Blog $blog): array
    {
        return [
            'blog' => $blog,
            'cities' => City::with('country')->whereHas('country', fn ($q) => $q->where('active', true))
                ->orWhereIn('id', $blog->exists ? $blog->cities()->pluck('cities.id') : [])->orderByDesc('active')->orderBy('name')->get(),
            'activities' => Activity::with(['city.country', 'partner', 'cover'])->orderBy('city_id')->orderBy('sort_order')->get(),
        ];
    }
}
