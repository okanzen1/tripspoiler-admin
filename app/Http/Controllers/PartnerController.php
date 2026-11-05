<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartnerRequest;
use App\Models\AffiliateClick;
use App\Models\Partner;
use App\Services\ImageUploader;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function __construct(private ImageUploader $uploader) {}

    public function index()
    {
        // Son 30 günün tıklamaları, partner koduna göre
        $clicks = AffiliateClick::where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->selectRaw('partner, COUNT(*) as total')
            ->groupBy('partner')
            ->pluck('total', 'partner');

        return view('admin.partners.index', [
            'partners' => Partner::orderByDesc('active')->orderBy('name')->get(),
            'clicks' => $clicks,
        ]);
    }

    public function create()
    {
        return view('admin.partners.create');
    }

    public function store(PartnerRequest $request)
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->uploader->logo($request->file('logo'));
        }

        $partner = Partner::create($data);

        return redirect()->route('partners.index')->with('success', __(':name eklendi.', ['name' => $partner->name]));
    }

    public function edit(Partner $partner)
    {
        return view('admin.partners.edit', [
            'partner' => $partner,
            'clickCount' => $partner->clicks()->count(),
        ]);
    }

    public function update(PartnerRequest $request, Partner $partner)
    {
        $data = $request->safe()->except(['logo', 'remove_logo']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $this->uploader->logo($request->file('logo'), replace: $partner->logo_path);
        } elseif ($request->boolean('remove_logo')) {
            $this->uploader->delete($partner->logo_path);
            $data['logo_path'] = null;
        }

        $partner->update($data);

        return redirect()->route('partners.index')->with('success', __(':name güncellendi.', ['name' => $partner->name]));
    }

    public function destroy(Partner $partner)
    {
        // Tıklama geçmişi olan partner silinmez; analitik bozulmasın diye pasif yapılır
        if ($partner->clicks()->exists()) {
            return back()->with('error', __(':name silinemez: tıklama geçmişi var. Bunun yerine pasif yap.', ['name' => $partner->name]));
        }

        $this->uploader->delete($partner->logo_path);
        $partner->delete();

        return redirect()->route('partners.index')->with('success', __(':name silindi.', ['name' => $partner->name]));
    }

    /**
     * Footer'ın üstündeki "Destekçilerimiz ve partnerlerimiz" şeridinde göster / gizle.
     */
    public function toggleShowcase(Request $request, Partner $partner)
    {
        $partner->update(['show_on_site' => !$partner->show_on_site]);
        $message = __($partner->show_on_site ? ':name partnerler şeridine eklendi.' : ':name partnerler şeridinden çıkarıldı.', ['name' => $partner->name]);
        if ($partner->show_on_site && !$partner->logo_path) {
            $message .= ' ' . __('Logosu yok; logo yükleyene kadar şeritte görünmez.');
        }

        return $request->expectsJson()
            ? response()->json(['active' => $partner->show_on_site, 'message' => $message, 'warning' => $partner->show_on_site && !$partner->logo_path])
            : back()->with('success', $message);
    }

    public function toggle(Request $request, Partner $partner)
    {
        $partner->update(['active' => !$partner->active]);

        $message = __($partner->active ? ':name aktif edildi.' : ':name pasif yapıldı.', ['name' => $partner->name]);

        return $request->expectsJson()
            ? response()->json(['active' => $partner->active, 'message' => $message])
            : back()->with('success', $message);
    }
}
