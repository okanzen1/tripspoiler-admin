<?php

namespace App\Http\Controllers;

use App\Models\AffiliatePartner;
use Illuminate\Http\Request;

class AffiliatePartnerController extends Controller
{
    public function index()
    {
        $partners = AffiliatePartner::orderBy('id')->paginate(10);

        return view('admin.affiliate_partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.affiliate_partners.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Sadece super admin partner oluşturabilir.');
        }

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ]);

        AffiliatePartner::create($data);

        return redirect()
            ->route('affiliate-partners.index')
            ->with('success', 'Affiliate partner oluşturuldu');
    }

    public function edit(AffiliatePartner $affiliate_partner)
    {
        return view('admin.affiliate_partners.edit', compact('affiliate_partner'));
    }

   public function update(Request $request, AffiliatePartner $affiliate_partner)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar güncelleyemez.');
        }

        $data = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ]);

        $affiliate_partner->update($data);

        return redirect()
            ->route('affiliate-partners.edit', $affiliate_partner)
            ->with('success', 'Affiliate partner güncellendi');
    }

    public function destroy(AffiliatePartner $affiliate_partner)
    {
        if (auth()->user()?->role !== 'super_admin') {
            return back()->withErrors('Super admin dışındaki kullanıcılar silemez.');
        }

        $affiliate_partner->delete();

        return redirect()
            ->route('affiliate-partners.index')
            ->with('success', 'Affiliate partner silindi');
    }
}
