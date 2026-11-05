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
        $scope = in_array($request->get('scope'), ['general', 'page', 'city', 'activity', 'blog'], true) ? $request->get('scope') : null;
        $activityId = $request->integer('activity') ?: null;
        $blogId = $request->integer('blog') ?: null;
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;

        $faqs = Faq::with(['activity', 'blog', 'city', 'page'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q->where('question->en', 'like', "%{$search}%")->orWhere('answer->en', 'like', "%{$search}%")))
            ->when($scope === 'general', fn ($q) => $q->general())
            ->when($scope === 'activity', fn ($q) => $q->whereNotNull('activity_id'))
            ->when($scope === 'blog', fn ($q) => $q->whereNotNull('blog_id'))
            ->when($scope === 'city', fn ($q) => $q->whereNotNull('city_id'))
            ->when($scope === 'page', fn ($q) => $q->whereNotNull('page_key'))
            ->when($activityId, fn ($q) => $q->where('activity_id', $activityId))
            ->when($blogId, fn ($q) => $q->where('blog_id', $blogId))
            ->when($status, fn ($q) => $q->where('active', $status === 'active'))
            ->orderByRaw('page_key IS NOT NULL, city_id IS NOT NULL, activity_id IS NOT NULL, blog_id IS NOT NULL')
            ->orderBy('page_key')
            ->orderBy('city_id')
            ->orderBy('activity_id')
            ->orderBy('blog_id')
            ->orderBy('sort_order')
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
            'counts' => [
                'general' => Faq::general()->count(),
                'page' => Faq::whereNotNull('page_key')->count(),
                'city' => Faq::whereNotNull('city_id')->count(),
                'activity' => Faq::whereNotNull('activity_id')->count(),
                'blog' => Faq::whereNotNull('blog_id')->count(),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $faq = new Faq(['active' => true]);
        $faq->activity_id = $request->integer('activity') ?: null;
        $faq->blog_id = $request->integer('blog') ?: null;

        return view('admin.faqs.form', $this->formData($faq));
    }

    public function store(FaqRequest $request)
    {
        $faq = new Faq([...$request->owner(), 'active' => $request->validated('active')]);
        $this->fillText($faq, $request);
        $faq->sort_order = (int) $this->siblings($faq)->max('sort_order') + 1;
        $faq->save();

        return redirect()->route('faqs.index')->with('success', __('Soru eklendi.'));
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
     * Soru ve cevap: sadece İngilizce yazılır, diğer diller korunur.
     */
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
