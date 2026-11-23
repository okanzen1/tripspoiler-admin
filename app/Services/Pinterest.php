<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\PinterestPin;
use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pinterest API v5 (ücretsiz): blogları pin olarak gönderir (Yönetim › Pinterest).
 * Uygulama kimliği / gizli anahtarı panelden girilir; "Pinterest'e bağlan" ile alınan erişim anahtarları
 * şifreli saklanır ve süresi dolmadan kendiliğinden yenilenir.
 *
 * Otomatik sıra (php artisan pinterest:send, saat başı): yayında + kapak resmi olan, daha önce başarıyla
 * gönderilmemiş bloglar, yayın tarihine göre eskiden yeniye; günde en fazla "pinterest_daily" (varsayılan 10)
 * gönderim, gün içine yayılır. Bir blog yalnızca bir kez gider; elle gönderim de kapak resmi ister.
 */
class Pinterest
{
    public const API = 'https://api.pinterest.com/v5';

    public const SCOPES = 'boards:read,boards:write,pins:read,pins:write,user_accounts:read';

    /** Şehri olmayan (genel) yazıların panosu; şehirlerinki "<Şehir> Travel Guide" */
    public const BOARD_NAME = 'TripSpoiler Travel Guides';

    public const BOARD_DESCRIPTION = 'City guides, travel tips, itineraries and things to do from tripspoiler.com.';

    /** Otomatik gönderimin yapıldığı saatler (sunucu saati) */
    public const FIRST_HOUR = 9;

    public const LAST_HOUR = 21;

    /** Gönderilemeyen blog otomatik olarak en fazla bu kadar denenir, denemeler arası en az RETRY_HOURS saat */
    public const MAX_TRIES = 3;

    public const RETRY_HOURS = 3;

    public static function appId(): ?string
    {
        return Setting::get('pinterest_app_id') ?: null;
    }

    public static function appSecret(): ?string
    {
        return self::decrypt(Setting::get('pinterest_app_secret'));
    }

    public static function hasApp(): bool
    {
        return self::appId() && self::appSecret();
    }

    public static function connected(): bool
    {
        return filled(Setting::get('pinterest_refresh_token'));
    }

    public static function boardId(): ?string
    {
        return Setting::get('pinterest_board_id') ?: null;
    }

    public static function enabled(): bool
    {
        return Setting::get('pinterest_enabled') === '1';
    }

    public static function daily(): int
    {
        return max(1, min(50, (int) (Setting::get('pinterest_daily') ?: 10)));
    }

    public static function redirectUri(): string
    {
        return route('pinterest.callback');
    }

    public function authorizeUrl(string $state): string
    {
        return 'https://www.pinterest.com/oauth/?' . http_build_query([
            'client_id' => self::appId(),
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);
    }

    /** "Pinterest'e bağlan" dönüşü: gelen kodu erişim anahtarlarına çevirir */
    public function connect(string $code): void
    {
        $this->storeTokens($this->tokenRequest(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => self::redirectUri()]));
        Setting::put('pinterest_account', $this->get('/user_account')['username'] ?? null);
        // bağlanınca otomatik gönderim açılır; panolar ilk gönderimde şehir şehir açılır
        Setting::put('pinterest_enabled', '1');
    }

    /**
     * Pano açma izni var mı. Pinterest izinleri bağlanırken bildirirse ona bakılır; bildirmediyse bilinmiyor sayılır
     * ve doğrudan denenir (Pinterest reddederse "yeniden bağlan" uyarısı çıkar).
     */
    public static function canCreateBoards(): bool
    {
        $scope = (string) Setting::get('pinterest_scope');

        return $scope === '' || str_contains($scope, 'boards:write');
    }

    public function disconnect(): void
    {
        foreach (['pinterest_access_token', 'pinterest_refresh_token', 'pinterest_token_expires_at', 'pinterest_account', 'pinterest_scope'] as $key) {
            Setting::put($key, null);
        }
    }

    /**
     * Blogun gideceği pano. Panelden tek pano seçildiyse hep o. Seçilmediyse her şehrin kendi panosu
     * ("Rome Travel Guide"); şehri olmayan genel yazılar "TripSpoiler Travel Guides". Pano Pinterest'te aynı adla
     * varsa o kullanılır, yoksa açılır; eşleşme "pinterest_boards" ayarında saklanır.
     */
    public function boardFor(Blog $blog): string
    {
        if ($id = self::boardId()) {
            return $id;
        }
        $city = $blog->cities()->orderBy('name')->first() ?? $blog->city;
        $key = $city ? 'city:' . $city->id : 'general';
        $map = self::boardMap();
        if (isset($map[$key]['id'])) {
            return $map[$key]['id'];
        }

        $name = $city ? $city->name . ' Travel Guide' : self::BOARD_NAME;
        $board = collect($this->boards())->first(fn ($b) => mb_strtolower($b['name']) === mb_strtolower($name));
        if (!$board) {
            try {
                $created = $this->request('post', '/boards', [
                'name' => $name,
                'description' => $city
                    ? "Things to do, travel tips, itineraries and guides for {$city->name} from tripspoiler.com."
                    : self::BOARD_DESCRIPTION,
                'privacy' => 'PUBLIC',
                ]);
            } catch (\RuntimeException $e) {
                // izin yok (eski bağlantı pano açma iznini içermiyor)
                if (in_array($e->getCode(), [401, 403], true)) {
                    throw new \RuntimeException(__('Pinterest\'te pano açma izni yok. Yönetim › Pinterest\'te "Bağlantıyı kaldır" deyip yeniden bağlan.') . ' (' . $e->getMessage() . ')', $e->getCode());
                }
                throw $e;
            }
            $board = ['id' => (string) $created['id'], 'name' => (string) ($created['name'] ?? $name)];
            $this->boards = null; // yeni pano listeye girsin
        }
        $map[$key] = $board;
        Setting::put('pinterest_boards', json_encode($map, JSON_UNESCAPED_UNICODE));

        return $board['id'];
    }

    /** Otomatik açılan / bulunan panolar: ['city:12' => ['id' => …, 'name' => 'Rome Travel Guide'], 'general' => …] */
    public static function boardMap(): array
    {
        return json_decode((string) Setting::get('pinterest_boards'), true) ?: [];
    }

    /** Pinterest'te silinen otomatik pano: eşleşme unutulur, sonraki denemede yeniden bulunur / açılır */
    private function forgetBoard(string $boardId): void
    {
        $map = array_filter(self::boardMap(), fn ($b) => ($b['id'] ?? null) !== $boardId);
        Setting::put('pinterest_boards', json_encode($map, JSON_UNESCAPED_UNICODE));
    }

    /** @var list<array{id: string, name: string}>|null istek boyunca bir kez çekilir */
    private ?array $boards = null;

    /** @return list<array{id: string, name: string}> */
    public function boards(): array
    {
        if ($this->boards !== null) {
            return $this->boards;
        }
        $boards = [];
        $bookmark = null;
        do {
            $page = $this->get('/boards', array_filter(['page_size' => 100, 'bookmark' => $bookmark]));
            foreach ($page['items'] ?? [] as $board) {
                $boards[] = ['id' => (string) $board['id'], 'name' => (string) $board['name']];
            }
            $bookmark = $page['bookmark'] ?? null;
        } while ($bookmark && count($boards) < 1000);

        return $this->boards = $boards;
    }

    /**
     * Blogu seçili panoya pin olarak gönderir; sonucu (başarılı ya da hatalı) kaydeder.
     */
    public function send(Blog $blog, bool $manual = false): PinterestPin
    {
        $pin = new PinterestPin(['blog_id' => $blog->id, 'board_id' => self::boardId(), 'manual' => $manual]);

        try {
            // bir blog yalnızca bir kez gider (otomatik de elle de)
            if ($blog->pinterestPins()->whereNotNull('pin_id')->exists()) {
                throw new \RuntimeException(__('Bu blog zaten Pinterest\'e gönderildi; aynı blog tekrar gönderilmez.'));
            }
            if (!$blog->cover_path) {
                throw new \RuntimeException(__('Blogun kapak resmi yok; Pinterest resimsiz pin kabul etmez.'));
            }
            $board = $this->boardFor($blog);
            $pin->board_id = $board;
            // her zaman İngilizce (panel dili ne olursa olsun)
            $title = (string) $blog->getTranslation('title', 'en', false);
            $description = (string) ($blog->getTranslation('excerpt', 'en', false) ?: $blog->getTranslation('meta_description', 'en', false));
            $response = $this->request('post', '/pins', [
                'board_id' => $board,
                'title' => Str::limit($title, 97),
                'description' => Str::limit(strip_tags($description), 490),
                'link' => $blog->siteUrl(),
                'alt_text' => Str::limit($title, 490),
                'media_source' => ['source_type' => 'image_url', 'url' => $blog->coverUrl()],
            ]);
            $pin->pin_id = (string) $response['id'];
        } catch (\Throwable $e) {
            $pin->error = Str::limit($e->getMessage(), 490);
            if ($e->getCode() === 404 && $pin->board_id && !self::boardId()) {
                $this->forgetBoard($pin->board_id);
            }
            // Uygulama henüz "Trial" erişimde: Pinterest gerçek pin açtırmaz. Otomatik gönderim durur, blog deneme hakkı
            // harcamadan sırada kalır (kayıt tutulmaz); Standard erişim gelince panelden yeniden açılır.
            if (self::isTrialError($e->getMessage())) {
                Setting::put('pinterest_enabled', '0');
                if (!self::trialBlocked()) {
                    \App\Support\Notify::admins('system', __('Pinterest otomatik gönderimi durdu'), __('Uygulama henüz "Trial" erişimde; Standard erişim gelince panelden yeniden aç.'), route('pinterest.index'));
                }
                Setting::put('pinterest_trial_blocked', '1');

                return $pin;
            }
        }
        $pin->save();

        return $pin;
    }

    public static function isTrialError(string $message): bool
    {
        return str_contains($message, 'Trial access');
    }

    /** Son denemede Pinterest "Trial erişim gerçek pin açamaz" dedi mi (Standard erişim bekleniyor) */
    public static function trialBlocked(): bool
    {
        return Setting::get('pinterest_trial_blocked') === '1';
    }

    /** Otomatik sıradaki bloglar: yayında, kapaklı, hiç başarıyla gönderilmemiş, eskiden yeniye */
    public static function queue()
    {
        return Blog::where('active', true)->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->whereNotNull('cover_path')
            ->whereDoesntHave('pinterestPins', fn ($q) => $q->whereNotNull('pin_id'))
            // gönderilemeyen blog RETRY_HOURS saat sonra yeniden denenir; MAX_TRIES kez olmazsa durur
            // (panelde "Tekrar sıraya al" ya da blog sayfasında "Pinterest'e gönder" ile yine gönderilebilir)
            ->whereDoesntHave('pinterestPins', fn ($q) => $q->whereNull('pin_id')->where('created_at', '>', now()->subHours(self::RETRY_HOURS)))
            ->has('pinterestPins', '<', self::MAX_TRIES)
            ->orderByRaw('published_at IS NULL')->orderBy('published_at')->orderBy('id');
    }

    /** Bugün otomatik sırayla yapılan gönderimler (hatalılar da sayılır: hata tekrar tekrar denenip günü doldurmasın) */
    public static function sentToday(): int
    {
        return PinterestPin::where('manual', false)->where('created_at', '>=', now()->startOfDay())->count();
    }

    /**
     * Saat başı çalışır: günlük hakkı kalan saatlere bölerek gönderir (10/gün ≈ saatte 1).
     *
     * @return int gönderilmeye çalışılan blog sayısı
     */
    public function sendScheduled(): int
    {
        if (!self::enabled() || !self::connected()) {
            return 0;
        }
        $hour = (int) now()->format('G');
        if ($hour < self::FIRST_HOUR || $hour > self::LAST_HOUR) {
            return 0;
        }
        $left = self::daily() - self::sentToday();
        if ($left <= 0) {
            return 0;
        }
        $now = (int) ceil($left / (self::LAST_HOUR - $hour + 1));

        $blogs = self::queue()->limit($now)->get();
        foreach ($blogs as $blog) {
            $this->send($blog);
        }

        return $blogs->count();
    }

    private function get(string $path, array $query = []): array
    {
        return $this->request('get', $path, $query);
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $response = Http::withToken($this->token())->acceptJson()->timeout(30)->{$method}(self::API . $path, $data);
        if ($response->failed()) {
            throw new \RuntimeException(__('Pinterest hatası (:code): :message', [
                'code' => $response->status(),
                'message' => $response->json('message') ?? Str::limit($response->body(), 200),
            ]), $response->status());
        }

        return $response->json() ?? [];
    }

    /** Geçerli erişim anahtarı; süresi bir günden az kaldıysa önce yenilenir */
    private function token(): string
    {
        if (!self::connected()) {
            throw new \RuntimeException(__('Pinterest bağlı değil.'));
        }
        if ((int) Setting::get('pinterest_token_expires_at') < now()->addDay()->timestamp) {
            $this->storeTokens($this->tokenRequest([
                'grant_type' => 'refresh_token',
                'refresh_token' => self::decrypt(Setting::get('pinterest_refresh_token')),
            ]));
        }

        return (string) self::decrypt(Setting::get('pinterest_access_token'));
    }

    private function tokenRequest(array $form): array
    {
        $response = Http::asForm()->withBasicAuth((string) self::appId(), (string) self::appSecret())
            ->acceptJson()->timeout(30)->post(self::API . '/oauth/token', $form);
        if ($response->failed() || !$response->json('access_token')) {
            throw new \RuntimeException(__('Pinterest bağlantısı yapılamadı (:code): :message', [
                'code' => $response->status(),
                'message' => $response->json('message') ?? Str::limit($response->body(), 200),
            ]));
        }

        return $response->json();
    }

    private function storeTokens(array $tokens): void
    {
        Setting::put('pinterest_access_token', Crypt::encryptString($tokens['access_token']));
        if (!empty($tokens['scope'])) {
            Setting::put('pinterest_scope', (string) $tokens['scope']);
        }
        if (!empty($tokens['refresh_token'])) { // yenilemede yeni refresh_token gelmeyebilir
            Setting::put('pinterest_refresh_token', Crypt::encryptString($tokens['refresh_token']));
        }
        Setting::put('pinterest_token_expires_at', (string) (now()->timestamp + (int) ($tokens['expires_in'] ?? 2592000)));
    }

    private static function decrypt(?string $value): ?string
    {
        try {
            return filled($value) ? Crypt::decryptString($value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Otomatik denemesi biten (MAX_TRIES kez gönderilemeyen) bloglar */
    public static function givenUp()
    {
        return Blog::whereDoesntHave('pinterestPins', fn ($q) => $q->whereNotNull('pin_id'))
            ->has('pinterestPins', '>=', self::MAX_TRIES);
    }
}
