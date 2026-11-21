<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Google Search Console API (ücretsiz, salt okuma): son 28 günün arama verisi panele gelir (Yönetim › Search Console).
 * Bağlantı bir Google Cloud "hizmet hesabı" ile: JSON anahtarı panele yapıştırılır (şifreli saklanır), hizmet
 * hesabının e-postası Search Console'da mülke kullanıcı olarak eklenir. Kullanıcı girişi yok, bağlantı kopmaz.
 * Her gün güncellenir (php artisan gsc:sync); Google verisi 2–3 gün geriden gelir.
 */
class SearchConsole
{
    public const API = 'https://searchconsole.googleapis.com/webmasters/v3';

    public const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public const DAYS = 28;

    /** API'nin tek istekte verdiği en fazla satır */
    public const PAGE_SIZE = 25000;

    /** Kaydedilen en fazla arama satırı (sayfa+arama); büyük sitelerde tablo şişmesin */
    public const MAX_ROWS = 100000;

    /** @return array{client_email: string, private_key: string}|null */
    public static function credentials(): ?array
    {
        try {
            $json = Setting::get('gsc_credentials');
            $data = $json ? json_decode(Crypt::decryptString($json), true) : null;
        } catch (\Throwable) {
            return null;
        }

        return isset($data['client_email'], $data['private_key']) ? $data : null;
    }

    public static function connected(): bool
    {
        return self::credentials() !== null;
    }

    public static function serviceEmail(): ?string
    {
        return self::credentials()['client_email'] ?? null;
    }

    public static function site(): ?string
    {
        return Setting::get('gsc_site') ?: null;
    }

    /**
     * Yapıştırılan JSON anahtarını doğrular ve şifreli kaydeder.
     */
    public static function saveCredentials(string $json): void
    {
        $data = json_decode($json, true);
        if (!is_array($data) || ($data['type'] ?? null) !== 'service_account' || empty($data['client_email']) || empty($data['private_key'])
            || !openssl_pkey_get_private($data['private_key'])) {
            throw new \InvalidArgumentException(__('Bu bir hizmet hesabı JSON anahtarı değil. Google Cloud\'dan indirdiğin .json dosyasının tamamını yapıştır.'));
        }
        Setting::put('gsc_credentials', Crypt::encryptString(json_encode([
            'client_email' => $data['client_email'],
            'private_key' => $data['private_key'],
        ])));
        Cache::forget('gsc:token');
    }

    public function disconnect(): void
    {
        foreach (['gsc_credentials', 'gsc_site', 'gsc_totals', 'gsc_synced_at', 'gsc_error'] as $key) {
            Setting::put($key, null);
        }
        Cache::forget('gsc:token');
        DB::table('search_console_rows')->delete();
    }

    /**
     * Hizmet hesabının erişebildiği Search Console mülkleri (ör. "sc-domain:tripspoiler.com", "https://tripspoiler.com/").
     *
     * @return list<string>
     */
    public function sites(): array
    {
        return collect($this->request('get', '/sites')['siteEntry'] ?? [])
            ->reject(fn ($site) => ($site['permissionLevel'] ?? '') === 'siteUnverifiedUser')
            ->pluck('siteUrl')->values()->all();
    }

    /**
     * Son 28 günün verisini çeker: toplam, sayfa toplamları ve sayfa+arama satırları.
     *
     * @return array{rows: int, error: ?string}
     */
    public function sync(): array
    {
        try {
            $site = self::site() ?? throw new \RuntimeException(__('Search Console mülkü seçilmemiş.'));
            $end = now()->subDays(2)->toDateString();
            $start = now()->subDays(2 + self::DAYS - 1)->toDateString();
            $range = ['startDate' => $start, 'endDate' => $end];

            $total = $this->query($site, $range + ['dimensions' => []])[0] ?? null;
            $pages = $this->rows($site, $range, ['page']);
            $queries = $this->rows($site, $range, ['query', 'page']);

            DB::transaction(function () use ($pages, $queries) {
                DB::table('search_console_rows')->delete();
                $insert = fn (array $rows, bool $withQuery) => collect($rows)->chunk(500)->each(fn ($chunk) => DB::table('search_console_rows')->insert(
                    $chunk->map(fn ($row) => [
                        'query' => $withQuery ? Str::limit((string) $row['keys'][0], 490, '') : null,
                        'page' => $page = (string) $row['keys'][$withQuery ? 1 : 0],
                        'page_hash' => sha1($page),
                        'clicks' => (int) $row['clicks'],
                        'impressions' => (int) $row['impressions'],
                        'ctr' => (float) $row['ctr'],
                        'position' => (float) $row['position'],
                    ])->values()->all()
                ));
                $insert($pages, false);
                $insert($queries, true);
            });

            Setting::put('gsc_totals', json_encode([
                'clicks' => (int) ($total['clicks'] ?? 0), 'impressions' => (int) ($total['impressions'] ?? 0),
                'ctr' => (float) ($total['ctr'] ?? 0), 'position' => (float) ($total['position'] ?? 0),
                'start' => $start, 'end' => $end,
            ]));
            Setting::put('gsc_synced_at', (string) now()->timestamp);
            Setting::put('gsc_error', null);

            return ['rows' => count($pages) + count($queries), 'error' => null];
        } catch (\Throwable $e) {
            Setting::put('gsc_error', Str::limit($e->getMessage(), 400));

            return ['rows' => 0, 'error' => Str::limit($e->getMessage(), 400)];
        }
    }

    /** @return array{clicks: int, impressions: int, ctr: float, position: float, start: string, end: string}|null */
    public static function totals(): ?array
    {
        return json_decode((string) Setting::get('gsc_totals'), true) ?: null;
    }

    /** Tüm satırlar, sayfa sayfa (API tek seferde 25.000 verir) */
    private function rows(string $site, array $range, array $dimensions): array
    {
        $all = [];
        for ($start = 0; $start < self::MAX_ROWS; $start += self::PAGE_SIZE) {
            $rows = $this->query($site, $range + ['dimensions' => $dimensions, 'rowLimit' => self::PAGE_SIZE, 'startRow' => $start, 'dataState' => 'final']);
            array_push($all, ...$rows);
            if (count($rows) < self::PAGE_SIZE) {
                break;
            }
        }

        return $all;
    }

    private function query(string $site, array $body): array
    {
        return $this->request('post', '/sites/' . rawurlencode($site) . '/searchAnalytics/query', $body)['rows'] ?? [];
    }

    private function request(string $method, string $path, array $body = []): array
    {
        $response = Http::withToken($this->token())->acceptJson()->timeout(60)->{$method}(self::API . $path, $body);
        if ($response->failed()) {
            $message = $response->json('error.message') ?? Str::limit($response->body(), 200);
            throw new \RuntimeException(match ($response->status()) {
                403 => __('Google erişim izni yok: hizmet hesabının e-postasını Search Console\'da bu mülke kullanıcı olarak ekle. (:message)', ['message' => $message]),
                default => __('Search Console hatası (:code): :message', ['code' => $response->status(), 'message' => $message]),
            }, $response->status());
        }

        return $response->json() ?? [];
    }

    /** Hizmet hesabıyla erişim anahtarı (JWT, RS256); 50 dakika önbellekte */
    private function token(): string
    {
        $credentials = self::credentials() ?? throw new \RuntimeException(__('Search Console bağlı değil.'));

        return Cache::remember('gsc:token', now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $encode = fn ($data) => rtrim(strtr(base64_encode(is_string($data) ? $data : json_encode($data)), '+/', '-_'), '=');
            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']) . '.' . $encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]);
            if (!openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException(__('Hizmet hesabı anahtarı okunamadı.'));
            }

            $response = Http::asForm()->timeout(30)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned . '.' . $encode($signature),
            ]);
            if ($response->failed() || !$response->json('access_token')) {
                throw new \RuntimeException(__('Google bağlantısı kurulamadı: :message', ['message' => $response->json('error_description') ?? $response->json('error') ?? $response->status()]));
            }

            return $response->json('access_token');
        });
    }
}
