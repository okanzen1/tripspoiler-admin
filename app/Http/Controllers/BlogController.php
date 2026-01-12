<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogContent;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'city_id' => 'nullable|exists:cities,id',
            'status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'themes' => 'nullable|string',
        ]);

        $locale = app()->getLocale();

        $blog = new Blog();
        $blog->city_id = $data['city_id'] ?? null;
        $blog->status = $data['status'] ?? false;
        $blog->sort_order = $data['sort_order'] ?? 0;
        $blog->click_count = 0;

        $blog->setTranslation('title', $locale, $data['title']);
        $blog->setTranslation('excerpt', $locale, $data['excerpt'] ?? '');
        $blog->setTranslation('meta_title', $locale, $data['meta_title'] ?? '');
        $blog->setTranslation('meta_description', $locale, $data['meta_description'] ?? '');

        // 🔥 THEMES
        if (!empty($data['themes'])) {
            $themesArray = collect(explode(',', $data['themes']))
                ->map(fn($i) => trim($i))
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
        $blog->load('images');
        $cities = City::where('active', true)->orderBy('name')->get();

        $contents = BlogContent::where('blog_id', $blog->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.blogs.edit', compact('blog', 'cities', 'contents'));
    }


    public function update(Request $request, Blog $blog)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer',
            'source' => 'nullable|string|max:255',
            'source_id' => 'nullable|string|max:255',
            'themes' => 'nullable|string',
        ]);

        $locale = app()->getLocale();

        $blog->city_id = $data['city_id'];
        $blog->status = $data['status'];
        $blog->sort_order = $data['sort_order'];
        $blog->source = $data['source'] ?? null;
        $blog->source_id = $data['source_id'] ?? null;

        $blog->setTranslation('title', $locale, $data['title']);
        $blog->setTranslation('excerpt', $locale, $data['excerpt'] ?? '');
        $blog->setTranslation('meta_title', $locale, $data['meta_title']);
        $blog->setTranslation('meta_description', $locale, $data['meta_description']);

        // 🔥 THEMES
        if (!empty($data['themes'])) {
            $themesArray = collect(explode(',', $data['themes']))
                ->map(fn($i) => trim($i))
                ->filter()
                ->values()
                ->toArray();

            $blog->setTranslation('themes', $locale, $themesArray);
        }

        $blog->save();

        return redirect()
            ->route('blogs.edit', $blog)
            ->with('success', 'Blog güncellendi.');
    }


    public function destroy(Blog $blog)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $blog->images()->each(function ($image) {
            $image->delete();
        });

        $blog->delete();

        return redirect()
            ->route('blogs.index')
            ->with('success', 'Blog silindi.');
    }
};
