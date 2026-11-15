<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Partner;
use App\Models\PartnerLink;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

/**
 * Aktiviteler › Partner bağlantıları: aktivite listesinin sonunda "Aradığını bulamadın mı?" kartları
 * (detay sayfası yok, partnerin şehir sayfasına doğrudan gider).
 */
class PartnerLinkController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index()
    {
        return view('admin.partner-links.index', [
            'links' => PartnerLink::with(['city.country', 'partner'])->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.partner-links.form', $this->formData(new PartnerLink(['active' => true])));
    }

    public function store(Request $request)
    {
        $link = PartnerLink::create([...$this->validated($request), 'sort_order' => (int) PartnerLink::max('sort_order') + 1]);
        $this->saveImage($link, $request);

        return redirect()->route('partner-links.index')->with('success', __('":name" bağlantısı eklendi.', ['name' => $link->partner->name]));
    }

    public function edit(PartnerLink $partnerLink)
    {
        return view('admin.partner-links.form', $this->formData($partnerLink));
    }

    public function update(Request $request, PartnerLink $partnerLink)
    {
        $partnerLink->update($this->validated($request));
        $this->saveImage($partnerLink, $request);

        return redirect()->route('partner-links.index')->with('success', __('Değişiklikler kaydedildi.'));
    }

    public function destroy(PartnerLink $partnerLink)
    {
        $this->uploader->delete($partnerLink->image_path);
        $partnerLink->delete();

        return back()->with('success', __('Bağlantı silindi.'));
    }

    public function toggle(Request $request, PartnerLink $partnerLink)
    {
        $partnerLink->update(['active' => !$partnerLink->active]);
        $message = __($partnerLink->active ? 'Bağlantı yayına alındı.' : 'Bağlantı yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $partnerLink->active, 'message' => $message])
            : back()->with('success', $message);
    }

    /** Oklarla sıra (yukarı / aşağı) */
    public function move(Request $request, PartnerLink $partnerLink)
    {
        $step = $request->integer('step') < 0 ? -1 : 1;
        $all = PartnerLink::orderBy('sort_order')->orderBy('id')->pluck('id')->values();
        $i = $all->search($partnerLink->id);
        $j = $i + $step;
        if ($j >= 0 && $j < $all->count()) {
            [$all[$i], $all[$j]] = [$all[$j], $all[$i]];
            foreach ($all as $position => $id) {
                PartnerLink::whereKey($id)->update(['sort_order' => $position + 1]);
            }
        }

        return back();
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'city_id' => ['nullable', 'exists:cities,id'],
            'partner_id' => ['required', 'exists:partners,id'],
            'url' => ['required', 'url:https,http', 'max:1000'],
            'count_label' => ['nullable', 'string', 'max:20'],
            'type' => ['nullable', 'in:' . implode(',', array_keys(PartnerLink::TYPES))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], ['city_id' => __('şehir'), 'partner_id' => __('partner'), 'url' => __('bağlantı'), 'count_label' => __('aktivite sayısı'), 'image' => __('resim')]);
        unset($data['image']);

        return [...$data, 'type' => ($data['type'] ?? null) ?: null, 'city_id' => ($data['city_id'] ?? null) ?: null, 'count_label' => trim((string) ($data['count_label'] ?? '')) ?: null, 'active' => $request->boolean('active')];
    }

    /** Kart resmi: yüklenirse eskisinin yerine geçer, "Kaldır" ile silinir */
    private function saveImage(PartnerLink $link, Request $request): void
    {
        if ($request->hasFile('image')) {
            $link->update(['image_path' => $this->uploader->cover($request->file('image'), replace: $link->image_path)]);
        } elseif ($request->boolean('remove_image') && $link->image_path) {
            $this->uploader->delete($link->image_path);
            $link->update(['image_path' => null]);
        }
    }

    private function formData(PartnerLink $link): array
    {
        return [
            'link' => $link,
            'cities' => City::with('country')->where(fn ($q) => $q->whereHas('country', fn ($c) => $c->where('active', true))->orWhere('id', $link->city_id))
                ->orderByDesc('active')->orderBy('name')->get(),
            'partners' => Partner::orderByDesc('active')->orderBy('name')->get(),
        ];
    }
}
