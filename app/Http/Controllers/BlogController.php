<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogContent;
use App\Models\City;
use Illuminate\Http\Request;
use App\Models\Activity;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with('city')
            ->orderBy('sort_order')
            ->paginate(10);

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        $cities = City::where('active', true)
            ->orderBy('name')
            ->get();

        return view('admin.blogs.create', compact('cities'));
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'city_id' => 'required|exists:cities,id',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'themes' => 'nullable|string',
        ]);

        $locale = app()->getLocale();

        $blog = new Blog();
        $blog->city_id = $data['city_id'];
        $blog->status = $data['status'] ?? false;
        $blog->sort_order = $data['sort_order'] ?? 0;
        $blog->click_count = 0;

        $blog->setTranslation('title', $locale, $data['title']);
        $blog->setTranslation('excerpt', $locale, $data['excerpt'] ?? '');
        $blog->setTranslation('meta_title', $locale, $data['meta_title'] ?? '');
        $blog->setTranslation('meta_description', $locale, $data['meta_description'] ?? '');

        if (!empty($data['themes'])) {
            $themesArray = collect(explode(',', $data['themes']))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->toArray();

            $blog->setTranslation('themes', $locale, $themesArray);
        }

        $blog->save();

        return redirect()
            ->route('blogs.edit', $blog)
            ->with('success', 'Blog oluşturuldu.');
    }

    public function edit(Blog $blog)
    {
        $blog->load('images', 'activities');
        $blogCityId = $blog->city_id;
        $cities = City::where('active', true)->orderBy('name')->get();
        $contents = BlogContent::where('blog_id', $blog->id)->orderBy('created_at', 'desc')->get();
        $activities = Activity::where('status', true)->orderBy('id', 'desc')
        ->where(function ($query) use ($blogCityId) {
            $query->where('city_id', $blogCityId)
                ->orWhereNull('city_id');
        })
        ->get();

        return view('admin.blogs.edit', compact('blog', 'cities', 'contents', 'activities'));
    }

    public function update(Request $request, Blog $blog)
    {

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer',
            'themes' => 'nullable|string',
            'activities' => 'nullable|array',
            'activities.*' => 'exists:activities,id',
        ]);

        $locale = app()->getLocale();

        $blog->status = $data['status'];
        $blog->sort_order = $data['sort_order'];

        $blog->setTranslation('title', $locale, $data['title']);
        $blog->setTranslation('excerpt', $locale, $data['excerpt'] ?? '');
        $blog->setTranslation('meta_title', $locale, $data['meta_title']);
        $blog->setTranslation('meta_description', $locale, $data['meta_description']);

        if (!empty($data['themes'])) {
            $themesArray = collect(explode(',', $data['themes']))
                ->map(fn ($item) => trim($item))
                ->filter()
                ->values()
                ->toArray();

            $blog->setTranslation('themes', $locale, $themesArray);
        }

        $blog->save();
        $blog->activities()->sync($request->activities ?? []);
        return redirect()
            ->route('blogs.edit', $blog)
            ->with('success', 'Blog güncellendi.');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();

        return redirect()
            ->route('blogs.index')
            ->with('success', 'Blog silindi.');
    }
}
