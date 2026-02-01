<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use App\Models\PageContent;
use Illuminate\Http\Request;

class PageContentController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()?->role === 'super_admin', 403);
    }

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
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'content' => 'nullable|string',
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
        $content->save();

        return back()->with('success', 'Şehir içeriği kaydedildi.');
    }
}
