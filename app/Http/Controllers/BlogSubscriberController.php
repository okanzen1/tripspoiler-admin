<?php

namespace App\Http\Controllers;

use App\Models\BlogSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Blog aboneleri: liste, CSV indirme, silme ve e-postadaki "abonelikten çık" bağlantısı.
 * E-postalar şifreli olduğu için arama tam adresle (hash) yapılır.
 */
class BlogSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));
        $status = in_array($request->get('status'), ['active', 'passive'], true) ? $request->get('status') : null;

        $subscribers = BlogSubscriber::query()
            ->when($search, fn ($q) => $q->where('email_hash', BlogSubscriber::hash($search)))
            ->when($status === 'active', fn ($q) => $q->whereNull('unsubscribed_at'))
            ->when($status === 'passive', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.subscribers.index', [
            'subscribers' => $subscribers,
            'search' => $search,
            'status' => $status,
            'activeCount' => BlogSubscriber::active()->count(),
            'totalCount' => BlogSubscriber::count(),
            'newThisMonth' => BlogSubscriber::where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }

    /**
     * Tüm aboneleri CSV olarak indirir (Excel uyumlu: UTF-8 BOM ve noktalı virgül).
     */
    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['E-posta', 'Abone olma tarihi', 'Durum', 'Sayfa'], ';', escape: '');

            foreach (BlogSubscriber::query()->lazyByIdDesc(500) as $subscriber) {
                fputcsv($handle, array_map($this->csvSafe(...), [
                    $subscriber->email,
                    $subscriber->created_at->format('d.m.Y H:i'),
                    $subscriber->isActive() ? 'Aktif' : 'Ayrıldı',
                    $subscriber->source_path,
                ]), ';', escape: '');
            }

            fclose($handle);
        }, 'blog-aboneleri-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(BlogSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', __('Abone listeden silindi.'));
    }

    /**
     * E-postadaki "abonelikten çık" bağlantısı (imzalı). GET onay sayfasını gösterir, POST çıkarır;
     * böylece e-posta tarayıcılarının bağlantıyı önceden açması aboneliği iptal etmez.
     */
    public function unsubscribe(BlogSubscriber $subscriber): View
    {
        return view('subscribers.unsubscribe', ['subscriber' => $subscriber, 'done' => !$subscriber->isActive()]);
    }

    public function confirmUnsubscribe(BlogSubscriber $subscriber): View
    {
        if ($subscriber->isActive()) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('subscribers.unsubscribe', ['subscriber' => $subscriber, 'done' => true]);
    }

    /**
     * Excel'in hücreyi formül olarak çalıştırmasını önler (CSV enjeksiyonu).
     */
    private function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
