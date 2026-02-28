<?php

namespace App\Http\Controllers;

use App\Models\PageContent;
use App\Models\CityExperienceCategory;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class CityExperienceCategoryController extends Controller
{
    public function index(PageContent $pageContent)
    {
        return response()->json(
            $pageContent->experienceCategories()->get()
        );
    }

    public function store(Request $request, PageContent $pageContent)
    {

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|boolean',
        ]);

        $category = new CityExperienceCategory();
        $category->page_content_id = $pageContent->id;

        // spatie translatable
        $category->setTranslation('name', app()->getLocale(), $data['name']);

        $category->sort_order = $data['sort_order'] ?? 0;
        $category->status = $data['status'];
        $category->save();

        return response()->json($category);
    }

    public function edit(CityExperienceCategory $category)
    {
        return view('admin.experience-categories.edit', compact('category'));
    }

    public function update(Request $request, CityExperienceCategory $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer',
            'description' => 'required|string',
        ]);

        $locale = app()->getLocale();

        // Name update
        $category->setTranslation('name', $locale, $data['name']);
        $category->sort_order = $data['sort_order'];
        $category->save();

        // Description
        $description = $category->descriptions()->first();

        if (!$description) {
            $description = $category->descriptions()->create([
                'description' => [],
            ]);
        }

        $description->setTranslation('description', $locale, $data['description']);
        $description->save();

        preg_match_all('/\/media\/(\d+)/', $data['description'], $matches);
        $usedImageIds = $matches[1] ?? [];

        $allImages = Image::where('source', 'city_experience_category_description')
            ->where('source_id', $description->id)
            ->get();

        foreach ($allImages as $image) {

            if (!in_array($image->id, $usedImageIds)) {

                Storage::disk('private')->delete($image->path);
                $image->delete();
            }
        }

        return redirect()
            ->back()
            ->with('success', 'Kategori güncellendi.');
    }

    public function destroy(CityExperienceCategory $category)
    {
        $pageContent = $category->pageContent;
        $page = $pageContent->page; 

        foreach ($category->descriptions as $description) {

            $images = Image::where('source', 'city_experience_category_description')
                ->where('source_id', $description->id)
                ->get();

            foreach ($images as $image) {
                Storage::disk('private')->delete($image->path);
                $image->delete();
            }
        }

        $category->delete();

        return redirect()
            ->route('pages.edit', $page->id)
            ->with('success', 'Kategori silindi.');
    }

    public function toggleStatus(CityExperienceCategory $category)
    {
        $category->update([
            'status' => ! $category->status,
        ]);

        return response()->json(['status' => $category->status]);
    }
}
