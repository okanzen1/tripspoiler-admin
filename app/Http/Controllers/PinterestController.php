<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\PinterestPin;
use App\Models\Setting;
use App\Services\Pinterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Yönetim › Pinterest: uygulama bilgileri, "Pinterest'e bağlan", pano ve günlük sayı; gönderilen / sıradaki /
 * gönderilemeyen bloglar. Blog düzenleme sayfasındaki "Pinterest'e gönder" düğmesi de buraya gelir.
 */
class PinterestController extends Controller
{
    public function __construct(private Pinterest $pinterest) {}

    public function index()
    {
        $boards = [];
        $boardsError = null;
        if (Pinterest::connected()) {
            try {
                $boards = $this->pinterest->boards();
            } catch (\Throwable $e) {
                $boardsError = $e->getMessage();
            }
        }

        $published = Blog::where('active', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
        // gönderilemeyenler: hiç başarılı gönderimi olmayan, en az bir hatası olan bloglar (son hata ve deneme sayısıyla)
        $failing = Blog::whereDoesntHave('pinterestPins', fn ($q) => $q->whereNotNull('pin_id'))->whereHas('pinterestPins')
            ->withCount('pinterestPins')->with(['pinterestPins' => fn ($q) => $q->limit(1)])->latest('updated_at')->limit(20)->get();

        return view('admin.pinterest.index', [
            'hasApp' => Pinterest::hasApp(),
            'appId' => Pinterest::appId(),
            'connected' => Pinterest::connected(),
            'account' => Setting::get('pinterest_account'),
            'boards' => $boards,
            'boardsError' => $boardsError,
            'boardId' => Pinterest::boardId(),
            'boardName' => collect($boards)->firstWhere('id', Pinterest::boardId())['name'] ?? Setting::get('pinterest_board_name'),
            'autoBoard' => Pinterest::BOARD_NAME,
            'boardMap' => Pinterest::boardMap(),
            'canCreateBoards' => Pinterest::canCreateBoards(),
            'enabled' => Pinterest::enabled(),
            'daily' => Pinterest::daily(),
            'redirectUri' => Pinterest::redirectUri(),
            'sentToday' => Pinterest::sentToday(),
            'queueCount' => Pinterest::queue()->count(),
            'next' => Pinterest::queue()->limit(6)->get(),
            'sentBlogs' => PinterestPin::sent()->distinct()->count('blog_id'),
            'noCover' => (clone $published)->whereNull('cover_path')->whereDoesntHave('pinterestPins', fn ($q) => $q->whereNotNull('pin_id'))->count(),
            'sent' => PinterestPin::sent()->with('blog')->latest('id')->limit(12)->get(),
            'failing' => $failing,
            'maxTries' => Pinterest::MAX_TRIES,
            'retryHours' => Pinterest::RETRY_HOURS,
        ]);
    }

    /** Pinterest geliştirici uygulamasının kimliği ve gizli anahtarı (gizli anahtar şifreli saklanır; boş bırakılırsa eskisi kalır) */
    public function saveApp(Request $request)
    {
        $data = $request->validate([
            'app_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'app_secret' => [Pinterest::appSecret() ? 'nullable' : 'required', 'string', 'max:200'],
        ]);
        Setting::put('pinterest_app_id', $data['app_id']);
        if (filled($data['app_secret'] ?? null)) {
            Setting::put('pinterest_app_secret', Crypt::encryptString(trim($data['app_secret'])));
        }

        return back()->with('success', __('Pinterest uygulama bilgileri kaydedildi. Şimdi "Pinterest\'e bağlan" düğmesine bas.'));
    }

    public function connect(Request $request)
    {
        abort_unless(Pinterest::hasApp(), 404);
        $state = Str::random(40);
        $request->session()->put('pinterest_state', $state);

        return redirect()->away($this->pinterest->authorizeUrl($state));
    }

    public function callback(Request $request)
    {
        $state = $request->session()->pull('pinterest_state');
        if (!$state || !hash_equals($state, (string) $request->query('state'))) {
            return redirect()->route('pinterest.index')->with('error', __('Bağlantı doğrulanamadı, tekrar dene.'));
        }
        if (!$request->filled('code')) {
            return redirect()->route('pinterest.index')->with('error', __('Pinterest izni verilmedi.'));
        }

        try {
            $this->pinterest->connect((string) $request->query('code'));
        } catch (\Throwable $e) {
            return redirect()->route('pinterest.index')->with('error', $e->getMessage());
        }

        return redirect()->route('pinterest.index')->with('success', __('Pinterest bağlandı. Bloglar günde :count tane, her şehrin kendi panosuna otomatik gidecek.', ['count' => Pinterest::daily()]));
    }

    public function disconnect()
    {
        $this->pinterest->disconnect();
        Setting::put('pinterest_enabled', '0');

        return back()->with('success', __('Pinterest bağlantısı kaldırıldı; otomatik gönderim durdu.'));
    }

    /** Pano, günlük sayı ve otomatik gönderim açık/kapalı */
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'board_id' => ['nullable', 'string', 'max:64', 'regex:/^\d+$/'],
            'daily' => ['required', 'integer', 'between:1,50'],
            'enabled' => ['boolean'],
        ]);
        // boş: otomatik pano (TripSpoiler Travel Guides) ilk gönderimde açılır / bulunur
        Setting::put('pinterest_board_id', $data['board_id'] ?? null);
        Setting::put('pinterest_board_name', $data['board_id'] ? (collect($this->pinterest->boards())->firstWhere('id', $data['board_id'])['name'] ?? null) : null);
        Setting::put('pinterest_daily', (string) $data['daily']);
        Setting::put('pinterest_enabled', $request->boolean('enabled') ? '1' : '0');

        return back()->with('success', $request->boolean('enabled')
            ? __('Kaydedildi. Yayındaki bloglar günde :count tane sırayla gönderilecek.', ['count' => $data['daily']])
            : __('Kaydedildi. Otomatik gönderim kapalı.'));
    }

    /** Gönderilemeyen blogların hata kayıtlarını siler: hepsi hemen yeniden sıraya girer */
    public function retryFailed()
    {
        $count = PinterestPin::failed()->distinct()->count('blog_id');
        PinterestPin::failed()->delete();

        return back()->with('success', __(':count blog tekrar sıraya alındı.', ['count' => $count]));
    }

    /** Blog düzenleme sayfasından elle gönder: sadece hiç gönderilmemiş ve kapak resmi olan blog */
    public function send(Blog $blog)
    {
        if (!Pinterest::connected()) {
            return back()->with('error', __('Önce Yönetim › Pinterest\'ten bağlan.'));
        }
        if ($blog->pinterestPins()->whereNotNull('pin_id')->exists()) {
            return back()->with('error', __('Bu blog zaten Pinterest\'e gönderildi; aynı blog tekrar gönderilmez.'));
        }
        if (!$blog->cover_path) {
            return back()->with('error', __('Pinterest\'e gitmesi için kapak resmi gerekli.'));
        }
        $pin = $this->pinterest->send($blog, manual: true);

        return back()->with($pin->pin_id ? 'success' : 'error', $pin->pin_id
            ? __('Blog Pinterest\'e gönderildi.')
            : __('Gönderilemedi: :error', ['error' => $pin->error]));
    }
}
