<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use App\Models\PageContent;
use Illuminate\Http\Request;

class PageContentController extends Controller
{
    /** AJAX: şehir içeriğini getir */
    public function show(Page $page, City $city)
    {
        return PageContent::where('page_id', $page->id)
            ->where('city_id', $city->id)
            ->first();
    }

    /** Kaydet / Güncelle */
    public function storeOrUpdate(Request $request, Page $page)
    {
        $data = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
        ]);

        $locale = app()->getLocale();

        $content = PageContent::firstOrCreate(
            [
                'page_id' => $page->id,
                'city_id' => $data['city_id'],
            ],
            [
                'is_active' => true,
            ]
        );

        $content->setTranslation('content', $locale, $data['content'] ?? '');
        $content->setTranslation('meta_title', $locale, $data['meta_title'] ?? '');
        $content->setTranslation('meta_description', $locale, $data['meta_description'] ?? '');
        $content->save();

        return back()->with('success', 'Şehir içeriği kaydedildi.');
    }

    public function saveTranslation(Request $request)
    {
        $data = $request->validate([
            'page_content_id' => 'required|exists:page_contents,id',
            'field' => 'required|in:meta_title,meta_description,content',
            'lang' => 'required|string|max:10',
            'text' => 'nullable|string',
        ]);

        $content = PageContent::findOrFail($data['page_content_id']);

        $content->setTranslation(
            $data['field'],
            $data['lang'],
            $data['text'] ?? ''
        );

        $content->save();

        return response()->json([
            'success' => true
        ]);
    }
}
