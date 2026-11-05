<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\SectionText;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Anasayfadaki blog bölümü: başlık yazıları (section_texts "blog"), öne çıkan yazılar (setting "home_blog_ids")
 * ve bülten kutusu (section_texts "newsletter": title = başlık, description = açıklama, eyebrow = buton yazısı).
 */
class BlogSectionController extends Controller
{
    public const MAX = 3;

    public function index()
    {
        return view('admin.blog-section.index', [
            'section' => SectionText::for('blog'),
            'newsletter' => SectionText::for('newsletter'),
            'blogs' => Blog::with('city')->orderByDesc('active')->orderByDesc('published_at')->orderByDesc('id')->get(),
            'picked' => array_values(array_filter(array_map('intval', explode(',', (string) Setting::get('home_blog_ids'))))),
            'max' => self::MAX,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'section.eyebrow' => ['nullable', 'string', 'max:40'],
            'section.title' => ['required', 'string', 'max:60'],
            'section.title_highlight' => ['nullable', 'string', 'max:60'],
            'section.description' => ['nullable', 'string', 'max:200'],
            'newsletter.title' => ['nullable', 'string', 'max:60'],
            'newsletter.description' => ['nullable', 'string', 'max:200'],
            'newsletter.eyebrow' => ['nullable', 'string', 'max:30'],
            'blogs' => ['nullable', 'array', 'max:' . self::MAX],
            'blogs.*' => ['integer', Rule::exists('blogs', 'id')],
        ], [], ['section.title' => __('başlık')]);

        foreach (['blog' => 'section', 'newsletter' => 'newsletter'] as $key => $input) {
            $record = SectionText::for($key);
            foreach ($data[$input] ?? [] as $field => $value) {
                filled($value) ? $record->setTranslation($field, 'en', trim($value)) : $record->forgetTranslation($field, 'en');
            }
            $record->save();
        }

        $ids = array_values(array_unique(array_map('intval', $data['blogs'] ?? [])));
        Setting::put('home_blog_ids', $ids ? implode(',', $ids) : null);

        return back()->with('success', __('Blog bölümü kaydedildi.'));
    }
}
