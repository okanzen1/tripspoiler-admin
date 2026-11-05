<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

/**
 * Sitenin sabit sayfaları: şimdilik SEO başlığı ve açıklaması.
 */
class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('sort_order')->get()]);
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function seo(Page $page)
    {
        return view('admin.pages.seo', compact('page'));
    }

    /**
     * Sayfanın bölümleri: sürükleyerek sıra, anahtarla aç/kapa (anasayfa hariç).
     */
    public function sections(Page $page)
    {
        abort_unless(\App\Support\PageSections::supports($page->key), 404);

        return view('admin.pages.sections', ['page' => $page, 'config' => \App\Support\PageSections::config($page->key)]);
    }

    /**
     * Her değişiklikte tüm durum gönderilir (sıra + açık olanlar); hemen kaydedilir.
     */
    public function updateSections(Request $request, Page $page)
    {
        abort_unless(\App\Support\PageSections::supports($page->key), 404);
        $data = $request->validate(['order' => ['array'], 'order.*' => ['string'], 'on' => ['array'], 'on.*' => ['string']]);
        \App\Support\PageSections::save($page->key, $data['order'] ?? [], $data['on'] ?? []);

        return response()->json(['message' => __('Kaydedildi. Sitede hemen görünür.')]);
    }

    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ], attributes: ['meta_title' => __('SEO başlığı'), 'meta_description' => __('SEO açıklaması')]);

        // Sadece İngilizce yazılır, diğer diller korunur
        foreach ($data as $field => $value) {
            filled($value) ? $page->setTranslation($field, 'en', $value) : $page->forgetTranslation($field, 'en');
        }
        $page->save();

        return redirect()->route('pages.edit', $page)->with('success', __(':name sayfasının SEO bilgileri kaydedildi.', ['name' => $page->name]));
    }
}
