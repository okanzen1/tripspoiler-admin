<?php

namespace App\Http\Controllers;

use App\Models\PageContent;
use App\Models\CityExperienceCategory;
use App\Models\CityExperienceCategoryDescription;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use App\Models\Translator;
use Illuminate\Http\Request;

class CityExperienceCategoryController extends Controller
{
    /**
     * Sabit şehir kategori listesi
     */
    private const FIXED_CITY_CATEGORIES = [
        "City Overview",
        "History & Identity",
        "Iconic Landmarks",
        "Neighborhood Guide",
        "Scenic Views",
        "Food & Local Culture",
        "Travel Tips",
        "Why Visit",
    ];


    public function saveDescriptionTranslation(Request $request)
    {
        $data = $request->validate([
            'description_id' => 'required|exists:city_experience_category_descriptions,id',
            'field' => 'required|in:description',
            'lang' => 'required|string|max:10',
            'text' => 'nullable|string',
        ]);

        $description = CityExperienceCategoryDescription::findOrFail($data['description_id']);

        $description->setTranslation(
            $data['field'],
            $data['lang'],
            $data['text'] ?? ''
        );

        $description->save();

        return response()->json([
            'success' => true
        ]);
    }
    /**
     * Liste
     */
    public function index(PageContent $pageContent)
    {
        return response()->json(
            $pageContent->experienceCategories()
                ->orderBy('sort_order')
                ->get()
        );
    }

    /**
     * Store (Sadece sabit kategoriler eklenebilir)
     */
    public function store(Request $request, PageContent $pageContent)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
            'status' => 'required|boolean',
        ]);

        if (!in_array($data['name'], self::FIXED_CITY_CATEGORIES)) {
            abort(403, 'Geçersiz kategori.');
        }

        $exists = $pageContent->experienceCategories()
            ->where('name->' . app()->getLocale(), $data['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Bu kategori zaten mevcut.'
            ], 422);
        }

        $category = new CityExperienceCategory();
        $category->page_content_id = $pageContent->id;
        $category->setTranslation('name', app()->getLocale(), $data['name']);
        $category->sort_order = $data['sort_order'] ?? 0;
        $category->status = $data['status'];
        $category->save();

        return response()->json($category);
    }

    /**
     * Edit
     */
    public function edit(CityExperienceCategory $category)
    {   
        $languages = Translator::where('active', 1)->where('code', '!=', 'en')->pluck('code');
        return view('admin.experience-categories.edit', compact('category', 'languages'));
    }

    /**
     * Update (İsim değiştirilemez)
     */
    public function update(Request $request, CityExperienceCategory $category)
    {
        $data = $request->validate([
            'sort_order' => 'required|integer',
            'description' => 'required|string',
        ]);

        $locale = app()->getLocale();

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

    /**
     * Destroy
     */
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

    /**
     * Toggle Status
     */
    public function toggleStatus(CityExperienceCategory $category)
    {
        $category->update([
            'status' => ! $category->status,
        ]);

        return response()->json([
            'status' => $category->status
        ]);
    }
}
