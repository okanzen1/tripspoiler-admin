<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignRequest;
use App\Models\Campaign;
use App\Models\Partner;
use Illuminate\Http\Request;

/**
 * Kampanyalar / indirim kodları: sitede /campaigns sayfası, üst menüde ve footer'da bağlantı.
 * Yayında (açık ve tarihi geçerli) kampanya yoksa sitede hiçbir yerde görünmez.
 */
class CampaignController extends Controller
{
    private const FIELDS = ['partner_id', 'code', 'url', 'starts_at', 'ends_at', 'active'];

    private const TRANSLATED = ['title', 'description', 'badge', 'note'];

    public function index()
    {
        return view('admin.campaigns.index', [
            'campaigns' => Campaign::with('partner')->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.campaigns.form', [
            'campaign' => new Campaign(['active' => true]),
            'partners' => Partner::orderBy('name')->get(),
        ]);
    }

    public function store(CampaignRequest $request)
    {
        $campaign = new Campaign($request->safe()->only(self::FIELDS));
        $this->fillTranslations($campaign, $request);
        $campaign->sort_order = (int) Campaign::max('sort_order') + 1;
        $campaign->save();

        return redirect()->route('campaigns.edit', $campaign)->with('success', __('Kampanya eklendi. Şimdi yukarıdan diğer dillere çevirebilirsin.'));
    }

    public function edit(Campaign $campaign)
    {
        return view('admin.campaigns.form', [
            'campaign' => $campaign,
            'partners' => Partner::orderBy('name')->get(),
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign)
    {
        $campaign->fill($request->safe()->only(self::FIELDS));
        $this->fillTranslations($campaign, $request);
        $campaign->save();

        return redirect()->route('campaigns.index')->with('success', __('Kampanya güncellendi.'));
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return back()->with('success', __('Kampanya silindi.'));
    }

    public function toggle(Request $request, Campaign $campaign)
    {
        $campaign->update(['active' => !$campaign->active]);
        $message = __($campaign->active ? 'Kampanya yayına alındı.' : 'Kampanya yayından kaldırıldı.');

        return $request->expectsJson()
            ? response()->json(['active' => $campaign->active, 'message' => $message])
            : back()->with('success', $message);
    }

    private function fillTranslations(Campaign $campaign, CampaignRequest $request): void
    {
        foreach (self::TRANSLATED as $field) {
            $value = $request->validated($field);
            if (filled($value)) {
                $campaign->setTranslation($field, 'en', $value);
            } elseif ($campaign->exists || $field !== 'title') {
                // İngilizcesi silindiyse alanın tamamı boşalır (eski çeviriler sitede kalmasın)
                $campaign->setTranslations($field, []);
            }
        }
    }
}
