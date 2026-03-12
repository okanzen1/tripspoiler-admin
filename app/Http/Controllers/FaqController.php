<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\Activity;
use App\Models\Blog;
use Illuminate\Http\Request;
use App\Models\Translator;

class FaqController extends Controller
{

    public const SOURCES = [
        'activity-show' => 'Activity Ürün Sayfası',
        'blog-show' => 'Blog Ürün Sayfası',
        'home' => 'Home Sayfası',
        'activity' => 'Activity Sayfası',
        'cities' => 'Cities Sayfası',
        'blog' => 'Blog Sayfası',
    ];

    public function saveTranslation(Request $request)
    {
        $faq = Faq::findOrFail($request->faq_id);

        $faq->setTranslation(
            $request->field,
            $request->lang,
            $request->text
        );

        $faq->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function index(Request $request)
    {
        $query = Faq::query();

        // Soru arama
        if ($request->filled('search')) {
            $query->where('question', 'like', '%' . $request->search . '%');
        }

        // Status filtre
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Source filtre
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        } elseif (!$request->filled('search') && !$request->filled('source_id')) {
            $query->where('source', 'home');
        }

        // Source ID filtre
        if ($request->filled('source_id')) {
            $query->where('source_id', $request->source_id);
        }

        $faqs = $query
            ->orderByDesc('id')
            ->orderBy('sort_order')
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Activity ve Blog isimlerini almak
        |--------------------------------------------------------------------------
        */

        $items = $faqs->getCollection();

        // activity-show idleri
        $activityIds = $items->where('source', 'activity-show')
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        // blog-show idleri
        $blogIds = $items->where('source', 'blog-show')
            ->pluck('source_id')
            ->filter()
            ->unique()
            ->values();

        // Activity map
        $activityMap = Activity::whereIn('id', $activityIds)
            ->get()
            ->mapWithKeys(function ($activity) {
                return [
                    $activity->id => $activity->id . ' - ' . $activity->name
                ];
            })
            ->toArray();

        // Blog map
        $blogMap = Blog::whereIn('id', $blogIds)
            ->get()
            ->mapWithKeys(function ($blog) {
                return [
                    $blog->id => $blog->id . ' - ' . $blog->title
                ];
            })
            ->toArray();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'sources' => self::SOURCES,
            'activityMap' => $activityMap,
            'blogMap' => $blogMap,
        ]);
    }

    public function create()
    {
        return view('admin.faqs.create', [
            'sources' => self::SOURCES,
        ]);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'question' => 'required|string|max:255',
            'source' => 'required|string',
        ]);

        $faq = Faq::create([
            'question' => $data['question'],
            'answer' => $data['answer'] ?? null,
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? false,
        ]);

        return redirect()
            ->route('faqs.edit', $faq)
            ->with('success', 'FAQ oluşturuldu');
    }

    public function edit(Faq $faq)
    {
        $activities = collect();
        $blogs = collect();

        if ($faq->source === 'activity-show') {
            $activities = Activity::select('id', 'name')
                ->orderBy('id', 'desc')
                ->get();
        }

        if ($faq->source === 'blog-show') {
            $blogs = Blog::select('id', 'title')
                ->orderBy('id', 'desc')
                ->get();
        }
        
        $languages = Translator::where('active', 1)->where('code', '!=', 'en')->pluck('code');
        
        return view('admin.faqs.edit', [
            'faq' => $faq,
            'sources' => self::SOURCES,
            'activities' => $activities,
            'blogs' => $blogs,
            'languages' => $languages,
        ]);
    }

    public function update(Request $request, Faq $faq)
    {

        $data = $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string',
            'source' => 'required|string',
            'source_id' => in_array($request->source, ['activity-show','blog-show']) ? 'required|integer' : 'nullable',
            'sort_order' => 'nullable|integer',
            'status' => 'boolean',
        ]);

        $faq->update([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? true,
        ]);

        return redirect()
            ->route('faqs.edit', $faq)
            ->with('success', 'FAQ güncellendi');
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();

        return redirect()
            ->route('faqs.index')
            ->with('success', 'FAQ silindi');
    }
}
