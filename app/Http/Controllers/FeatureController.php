<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeatureRequest;
use App\Models\Feature;
use Illuminate\Http\Request;

/**
 * Anasayfadaki özellik kartları (telefonlu bölümün altında yan yana kayar).
 */
class FeatureController extends Controller
{
    public function index()
    {
        return view('admin.features.index', [
            'features' => Feature::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.features.form', ['feature' => new Feature(['active' => true, 'icon' => 'sparkles'])]);
    }

    public function store(FeatureRequest $request)
    {
        $feature = new Feature($request->safe()->only(['active']));
        $this->fillText($feature, $request);
        if (!$this->fillIcon($feature, $request)) {
            return back()->withInput()->withErrors(['icon_upload' => __('Kendi ikonunu seçtin ama dosya yüklemedin.')]);
        }
        $feature->sort_order = (int) Feature::max('sort_order') + 1;
        $feature->save();

        // Eklenen kaydın sayfasında kal: hemen diğer dillere çevrilebilsin
        return redirect()->route('features.edit', $feature)->with('success', __('Kart eklendi.') . ' ' . __('Şimdi yukarıdaki Çeviriler kutusundan diğer dillere çevirebilirsin.'));
    }

    public function edit(Feature $feature)
    {
        return view('admin.features.form', ['feature' => $feature]);
    }

    public function update(FeatureRequest $request, Feature $feature)
    {
        $feature->fill($request->safe()->only(['active']));
        $this->fillText($feature, $request);
        if (!$this->fillIcon($feature, $request)) {
            return back()->withInput()->withErrors(['icon_upload' => __('Kendi ikonunu seçtin ama dosya yüklemedin.')]);
        }
        $feature->save();

        return redirect()->route('features.index')->with('success', __('Kart güncellendi.'));
    }

    public function destroy(Feature $feature)
    {
        $feature->delete();

        return back()->with('success', __('Kart silindi.'));
    }

    public function toggle(Request $request, Feature $feature)
    {
        $feature->update(['active' => !$feature->active]);
        $message = __($feature->active ? 'Kart yayına alındı.' : 'Kart yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $feature->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /**
     * Sırayı bir yukarı (-1) ya da aşağı (1) taşır: komşusuyla yer değiştirir.
     */
    public function move(Request $request, Feature $feature)
    {
        $step = $request->integer('step') < 0 ? -1 : 1;
        $ordered = Feature::orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $ordered->search(fn (Feature $item) => $item->is($feature));
        $neighbour = $ordered->get($index + $step);

        if ($neighbour) {
            // Sıralar eşit olabilir; önce hepsini 1..n yap, sonra ikisini değiştir (listedeki kopyalar kaydedilir)
            $current = $ordered[$index];
            $ordered->each(fn (Feature $item, int $i) => $item->sort_order = $i + 1);
            [$current->sort_order, $neighbour->sort_order] = [$neighbour->sort_order, $current->sort_order];
            $ordered->each->saveQuietly();
        }

        return back();
    }

    /**
     * İkon: hazır ikonlardan biri ya da yüklenen dosya ("custom"). Dosya yoksa false.
     */
    private function fillIcon(Feature $feature, FeatureRequest $request): bool
    {
        $choice = $request->validated('icon');

        if ($request->hasFile('icon_upload')) {
            try {
                $feature->icon_path = app(\App\Services\ImageUploader::class)->icon($request->file('icon_upload'), $feature->icon_path);
            } catch (\InvalidArgumentException $e) {
                throw \Illuminate\Validation\ValidationException::withMessages(['icon_upload' => $e->getMessage()]);
            }
            $choice = 'custom';
        }

        if ($choice === 'custom') {
            return (bool) $feature->icon_path;
        }

        // Hazır ikon seçildi: varsa yüklenen ikon kaldırılır
        app(\App\Services\ImageUploader::class)->delete($feature->icon_path);
        $feature->icon_path = null;
        $feature->icon = $choice;

        return true;
    }

    /**
     * Başlık ve yazı: sadece İngilizce yazılır, diğer diller korunur.
     */
    private function fillText(Feature $feature, FeatureRequest $request): void
    {
        $feature->setTranslation('title', 'en', $request->validated('title'));
        filled($request->validated('text'))
            ? $feature->setTranslation('text', 'en', $request->validated('text'))
            : $feature->forgetTranslation('text', 'en');
    }
}
