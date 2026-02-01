<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private function ensureSuperAdmin(): void
    {
        abort_unless(auth()->check() && auth()->user()?->role === 'super_admin', 403);
    }

    public function index()
    {
        $pages = Page::orderBy('id')->get();
        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:pages,slug',
        ]);

        $page = Page::create($data);

        return redirect()
            ->route('pages.edit', $page)
            ->with('success', 'Sayfa oluşturuldu.');
    }

    public function edit(Page $page)
    {
        $cities = City::orderBy('name')->get();

        // İstanbul id sabit (senin dediğin)
        $defaultCityId = 1;

        return view('admin.pages.edit', compact('page', 'cities', 'defaultCityId'));
    }

    public function update(Request $request, Page $page)
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'slug' => 'required|string|max:255|unique:pages,slug,' . $page->id,
        ]);

        $page->update($data);

        return redirect()
            ->route('pages.edit', $page)
            ->with('success', 'Sayfa güncellendi.');
    }

    public function destroy(Page $page)
    {
        $this->ensureSuperAdmin();

        $page->delete();

        return redirect()
            ->route('pages.index')
            ->with('success', 'Sayfa silindi.');
    }
}
