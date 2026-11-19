<?php

namespace App\Http\Controllers;

use App\Models\HelpRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Yardım talepleri (sitedeki "Plan your trip" formu): liste, ayrıntı, durum (Yeni / Cevaplandı / Kapandı), silme.
 * Cevap panelin e-postasından yazılır ("Cevapla" alıcıyı doldurur); veriler şifreli, açılınca çözülür.
 */
class HelpRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists((string) $request->get('status'), HelpRequest::STATUSES) ? $request->get('status') : null;

        // Talep numarasıyla arama (misafir "TS-48217" yazar; "48217" de olur)
        $search = strtoupper(trim((string) $request->get('q')));
        $reference = $search === '' ? null : (str_starts_with($search, 'TS-') ? $search : 'TS-' . ltrim($search, '#'));

        $requests = HelpRequest::query()
            ->when($reference, fn ($q) => $q->where('reference', $reference))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $counts = HelpRequest::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        // Son 90 günde en çok istenen şehirler ve ihtiyaçlar (şifre çözülerek sayılır; kayıt sayısı küçük)
        $recent = HelpRequest::where('created_at', '>=', now()->subDays(90))->get(['answers']);
        $topCities = $recent->flatMap(fn ($r) => (array) ($r->answers['city_names'] ?? []))->countBy()->sortDesc()->take(5);
        $topNeeds = $recent->flatMap(fn ($r) => HelpRequest::label('needs', (array) ($r->answers['needs'] ?? [])))->countBy()->sortDesc()->take(5);

        return view('admin.help-requests.index', [
            'requests' => $requests,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
            'total' => $counts->sum(),
            'topCities' => $topCities,
            'topNeeds' => $topNeeds,
        ]);
    }

    public function show(HelpRequest $helpRequest)
    {
        return view('admin.help-requests.show', ['item' => $helpRequest, 'others' => $helpRequest->others()]);
    }

    public function update(Request $request, HelpRequest $helpRequest)
    {
        $status = $request->validate(['status' => ['required', Rule::in(array_keys(HelpRequest::STATUSES))]])['status'];

        $helpRequest->update([
            'status' => $status,
            'answered_at' => $status === 'answered' ? ($helpRequest->answered_at ?? now()) : $helpRequest->answered_at,
        ]);

        return back()->with('success', __('Durum güncellendi: :status', ['status' => __(HelpRequest::STATUSES[$status])]));
    }

    public function destroy(HelpRequest $helpRequest)
    {
        $helpRequest->delete();

        return redirect()->route('help-requests.index')->with('success', __('Talep silindi.'));
    }
}
