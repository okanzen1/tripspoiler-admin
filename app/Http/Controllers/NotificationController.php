<?php

namespace App\Http\Controllers;

use App\Support\Notify;
use App\Support\Queues;
use App\Support\TranslationJobs;
use Illuminate\Http\Request;

/**
 * Bildirimler: sağ üstteki zil (özet, 30 sn'de bir) ve Bildirimler sayfası. Okundu bilgisi kişiye özel.
 */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('filtre') === 'okunmamis' ? 'unread' : 'all';
        $kind = array_key_exists((string) $request->query('tur'), Notify::KINDS) ? $request->query('tur') : null;

        $notifications = $request->user()->notifications()
            ->when($filter === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($kind, fn ($q) => $q->where('data', 'like', '%"kind":"' . $kind . '"%'))
            ->paginate(30)->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications->through(fn ($n) => Notify::present($n)),
            'filter' => $filter,
            'kind' => $kind,
            'unread' => $request->user()->unreadNotifications()->count(),
            'running' => TranslationJobs::active(),
            'bulk' => Queues::bulkCount(),
            'doneHour' => \App\Support\RecentTranslations::lastHour(),
            'schedulerAlive' => Queues::schedulerAlive(),
        ]);
    }

    /** Zil için özet: okunmamış sayısı, son bildirimler, süren çeviriler */
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $user->notifications()->limit(8)->get()->map(fn ($n) => Notify::present($n))->all(),
            'running' => array_map(fn ($job) => ['key' => $job['key'], 'title' => $job['title'], 'done' => $job['done'], 'total' => $job['total'], 'message' => $job['message']], TranslationJobs::active()),
            'bulk' => Queues::bulkCount(),
            'done_hour' => \App\Support\RecentTranslations::lastHour(),
        ]);
    }

    /** Bildirime tıklandı: okundu yapılır, ilgili sayfaya gidilir */
    public function open(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('notifications.index'));
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', __('Bütün bildirimler okundu olarak işaretlendi.'));
    }

    /** Okunmuş bildirimleri sil (sadece bu kullanıcınınkiler) */
    public function clearRead(Request $request)
    {
        $request->user()->readNotifications()->delete();

        return back()->with('success', __('Okunmuş bildirimler silindi.'));
    }
}
