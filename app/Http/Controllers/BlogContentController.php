<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogContent;
use Illuminate\Http\Request;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class BlogContentController extends Controller
{
    public function create(Blog $blog)
    {
        return view('admin.blog_contents.create', compact('blog'));
    }

    public function store(Request $request, Blog $blog)
    {

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $locale = app()->getLocale();

        $content = BlogContent::create([
            'blog_id' => $blog->id,
            'title' => [
                $locale => $data['title'],
            ],
            'content' => [],
            'status' => false,
            'sort_order' => 0,
        ]);

        return redirect()
            ->route('blogs.content.edit', [$blog, $content])
            ->with('success', 'İçerik oluşturuldu, şimdi düzenleyebilirsin.');
    }

    public function edit(Blog $blog, BlogContent $content)
    {
        abort_if($content->blog_id !== $blog->id, 404);

        return view('admin.blog_contents.edit', compact('blog', 'content'));
    }

    public function update(Request $request, Blog $blog, BlogContent $content)
    {
        abort_if($content->blog_id !== $blog->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer',
        ]);

        $locale = app()->getLocale();

        $content->setTranslation('title', $locale, $data['title']);
        $content->setTranslation('content', $locale, $data['content']);
        $content->status = $data['status'];
        $content->sort_order = $data['sort_order'];
        $content->save();

        preg_match_all('/\/media\/(\d+)/', $data['content'], $matches);
        $usedImageIds = $matches[1] ?? [];

        $allImages = Image::where('source', 'blog_content')
            ->where('source_id', $content->id)
            ->get();

        foreach ($allImages as $image) {

            if (!in_array($image->id, $usedImageIds)) {

                Storage::disk('private')->delete($image->path);
                $image->delete();
            }
        }

        return redirect()
            ->route('blogs.content.edit', [$blog, $content])
            ->with('success', 'İçerik güncellendi.');
    }

    public function destroy(Blog $blog, BlogContent $content)
    {
        abort_if($content->blog_id !== $blog->id, 404);

        $content->delete();

        return back()->with('success', 'İçerik silindi.');
    }

}
