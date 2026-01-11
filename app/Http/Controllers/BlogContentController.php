<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogContent;
use App\Models\Image;
use Illuminate\Http\Request;

class BlogContentController extends Controller
{
    public function create(Blog $blog)
    {
        $content = BlogContent::where('blog_id', $blog->id)->first();

        if ($content) {
            return redirect()->route('blogs.content.edit', [$blog, $content]);
        }

        return view('admin.blog_contents.create', compact('blog'));
    }

    public function store(Request $request, Blog $blog)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $locale = app()->getLocale();

        $content = BlogContent::where('blog_id', $blog->id)->first();
        if ($content) {
            return redirect()->route('blogs.content.edit', [$blog, $content]);
        }

        $content = BlogContent::create([
            'blog_id' => $blog->id,
            'title' => [
                $locale => $data['title'],
            ],
            'excerpt' => [],
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
        $this->authorizeSuperAdmin();
        abort_if($content->blog_id !== $blog->id, 404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'status' => 'required|boolean',
            'sort_order' => 'required|integer',
        ]);

        $locale = app()->getLocale();

        $oldHtml = $content->content ?? '';

        $newHtml = $data['content'];

        preg_match_all('#/media/(\d+)#', $oldHtml, $oldMatches);
        preg_match_all('#/media/(\d+)#', $newHtml, $newMatches);

        $oldImageIds = collect($oldMatches[1] ?? [])->unique();
        $newImageIds = collect($newMatches[1] ?? [])->unique();

        $deletedImageIds = $oldImageIds->diff($newImageIds);

        if ($deletedImageIds->isNotEmpty()) {
            Image::whereIn('id', $deletedImageIds)
                ->where('source', 'blog_content')
                ->where('source_id', $content->id)
                ->get()
                ->each(function ($image) {
                    $image->delete();
                });
        }

        if (!empty($data['excerpt'])) {
            $content->setTranslation('excerpt', $locale, $data['excerpt']);
        }

        $content->setTranslation('content', $locale, $newHtml);
        $content->save();

        return redirect()
            ->route('blogs.edit', $blog)
            ->with('success', 'İçerik güncellendi.');
    }

    public function destroy(Blog $blog, BlogContent $content)
    {
        $this->authorizeSuperAdmin();
        abort_if($content->blog_id !== $blog->id, 404);

        Image::where('source', 'blog_content')
            ->where('source_id', $content->id)
            ->get()
            ->each(function ($image) {
                $image->delete();
            });

        $content->delete();

        return back()->with('success', 'İçerik ve görselleri silindi.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (auth()->user()?->role !== 'super_admin') {
            abort(403, 'Yetkin yok.');
        }
    }
}
