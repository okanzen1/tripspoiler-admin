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

        $themes = null;
        if (!empty($data['themes'])) {
            $themes = array_values(
                array_filter(
                    array_map('trim', explode(',', $data['themes']))
                )
            );
        }

        $blog = Blog::create([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'slug' => Str::slug($data['title']),
            'city_id' => $data['city_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? false,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'themes' => $themes,
        ]);

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
            'slug' => 'required|string|max:255',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string',
            'city_id' => 'required|exists:cities,id',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer',
            'source' => 'nullable|string|max:255',
            'source_id' => 'nullable|string|max:255',
            'themes' => 'nullable|string',
        ]);

        $themes = null;
        if (!empty($data['themes'])) {
            $themes = array_values(
                array_filter(
                    array_map('trim', explode(',', $data['themes']))
                )
            );
        }

        $blog->update([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'slug' => Str::slug($data['title']),
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'status' => $data['status'],
            'sort_order' => $data['sort_order'] ?? 0,
            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'themes' => $themes,
        ]);

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
