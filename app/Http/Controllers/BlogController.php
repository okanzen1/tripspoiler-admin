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

        $blogs = Blog::with('city.country')->withCount('activities')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('title->en', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->when($status, fn ($q) => $q->where('active', $status === 'active'))
            ->when($cityId === 'none', fn ($q) => $q->whereNull('city_id'))
            ->when(is_numeric($cityId), fn ($q) => $q->where('city_id', (int) $cityId))
            ->when($cover, fn ($q) => $cover === 'yes' ? $q->whereNotNull('cover_path') : $q->whereNull('cover_path'))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // Son 30 günün okunması (analitikteki sayfa görüntülemeleri)
        $views = Visit::where('page_type', 'blog')->whereIn('page_id', $blogs->pluck('id'))
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('page_id, COUNT(*) as total')->groupBy('page_id')->pluck('total', 'page_id');

        return view('admin.blogs.index', [
            'blogs' => $blogs,
            'views' => $views,
            'search' => $search,
            'status' => $status,
            'cityId' => $cityId,
            'cover' => $cover,
            'filterCities' => City::with('country')->whereHas('blogs')->orderBy('name')->get(),
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
            FaqSync::sync($blog->faqs(), $request->validated('faqs') ?? []);

            return $blog;
        });

        // Eklenen kaydın sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('blogs.edit', $blog)->with('success', __('":name" eklendi.', ['name' => $blog->title]) . ' ' . __('Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.form', $this->formData($blog->load(['activities', 'faqs'])));
    }

    public function update(BlogRequest $request, Blog $blog)
    {
        DB::transaction(function () use ($request, $blog) {
            $this->fill($blog, $request);
            $blog->save();
            $this->syncActivities($blog, $request);
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
            'blogs' => $city ? Blog::where('city_id', $city->id)->orderByRaw('sort_order = 0 desc')->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id')->get() : collect(),
        ]);
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
            'city_id' => $data['city_id'],
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
                ->orWhere('id', $blog->city_id)->orderByDesc('active')->orderBy('name')->get(),
            'activities' => Activity::with('city')->orderBy('city_id')->orderBy('sort_order')->get(),
        ];
    }
}
