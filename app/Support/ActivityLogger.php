<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Panel işlem kaydı (Yönetim > İşlem kaydı): kim, ne zaman, neyi, nasıl değiştirdi.
 *
 * Bütün modellerin ekleme/güncelleme/silme olaylarını merkezden dinler; yeni eklenen sayfalar da kendiliğinden
 * kaydedilir. Yalnızca panelde giriş yapmış kullanıcının işlemleri yazılır (komutlar, site ziyaretleri değil).
 * Bir istekteki aynı kayda ait değişiklikler tek satırda birleşir (ör. Hero kaydında değişen bütün ayarlar).
 * Ayrıca: giriş, çıkış, başarısız giriş; arka plan çevirisi (TranslationJobs).
 */
class ActivityLogger
{
    /** Kaydedilmeyen modeller (kendi kayıtları, istatistik ve teknik tablolar) */
    private const IGNORED = [
        \App\Models\ActivityLog::class,
        \App\Models\TranslationState::class,
        \App\Models\Visit::class,
        \App\Models\AffiliateClick::class,
        // Claude bağlantısının giriş anahtarları (teknik; bağlantı İşlem kaydı'nda "giriş" olarak görünmez)
        \Laravel\Passport\Token::class,
        \Laravel\Passport\RefreshToken::class,
        \Laravel\Passport\AuthCode::class,
        \Laravel\Passport\Client::class,
        \Laravel\Passport\DeviceCode::class,
    ];

    /** Hiçbir zaman yazılmayan alanlar */
    private const HIDDEN = ['password', 'remember_token', 'created_at', 'updated_at', 'email_verified_at'];

    /** Tür → [panelde görünen ad, düzenleme rotası] */
    public const TYPES = [
        'activity' => ['Aktivite', 'activities.edit'],
        'activity_image' => ['Aktivite görseli', null],
        'announcement' => ['Duyuru', 'announcements.edit'],
        'blog' => ['Blog yazısı', 'blogs.edit'],
        'blog_subscriber' => ['Bülten abonesi', null],
        'bulk_email' => ['Toplu e-posta', null],
        'city' => ['Şehir', 'cities.edit'],
        'city_guide' => ['Şehir rehberi', null],
        'city_guide_section' => ['Rehber bölümü', null],
        'city_guide_topic' => ['Rehber konusu', 'city-guide-topics.edit'],
        'country' => ['Ülke', null],
        'faq' => ['SSS', 'faqs.edit'],
        'feature' => ['Özellik kartı', 'features.edit'],
        'hero_text' => ['Anasayfa hero yazısı', null],
        'language' => ['Dil', null],
        'mail_template' => ['E-posta şablonu', null],
        'page' => ['Sayfa', 'pages.edit'],
        'page_banner' => ['Sayfa banner\'ı', null],
        'partner' => ['Partner', 'partners.edit'],
        'redirect' => ['Yönlendirme', null],
        'review' => ['Yorum', 'reviews.edit'],
        'section_text' => ['Bölüm yazısı', null],
        'setting' => ['Ayarlar', null],
        'step' => ['Adım', 'steps.edit'],
        'user' => ['Kullanıcı', 'users.edit'],
    ];

    /** @var array<string, array> bu istekte birikenler: "işlem|tür|id" => satır */
    private static array $buffer = [];

    private static bool $flushRegistered = false;

    public static function register(): void
    {
        foreach (['created', 'updated', 'deleted'] as $event) {
            Event::listen("eloquent.{$event}: *", function (string $name, array $payload) use ($event) {
                $model = $payload[0] ?? null;
                if ($model instanceof Model) {
                    self::model($event, $model);
                }
            });
        }

        Event::listen(Login::class, fn (Login $e) => self::write('login', user: $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && self::write('logout', user: $e->user));
        Event::listen(Failed::class, fn (Failed $e) => self::write('login_failed', label: Str::limit((string) ($e->credentials['email'] ?? ''), 120, '')));
    }

    /** Model olayı → (birleştirilerek) kayıt */
    private static function model(string $event, Model $model): void
    {
        if (!auth()->check() || in_array($model::class, self::IGNORED, true)) {
            return;
        }

        $type = Str::snake(class_basename($model));
        $changes = $event === 'updated' ? self::diff($model) : [];
        if ($event === 'updated' && !$changes) {
            return;
        }

        // Ayarlar tek tek değil, istekte değişen hepsi tek satır: "Ayarlar güncellendi"
        $isSetting = $type === 'setting';
        if ($isSetting) {
            $key = (string) $model->getKey();
            $changes = $event === 'deleted'
                ? [$key => [self::short($model->getOriginal('value')), null]]
                : [$key => [$event === 'created' ? null : self::short($model->getOriginal('value')), self::short($model->getAttribute('value'))]];
            $event = 'updated';
            // Boş ayarın boş olarak oluşturulması gibi gerçekte değişmeyenler yazılmaz
            if ($changes[$key][0] === $changes[$key][1]) {
                return;
            }
        }

        $id = $isSetting ? null : $model->getKey();
        $bufferKey = "{$event}|{$type}|{$id}";
        if (isset(self::$buffer[$bufferKey])) {
            self::$buffer[$bufferKey]['changes'] = array_merge(self::$buffer[$bufferKey]['changes'] ?? [], $changes);

            return;
        }

        self::$buffer[$bufferKey] = [
            'action' => $event,
            'subject_type' => $type,
            'subject_id' => is_numeric($id) ? (int) $id : null,
            'subject_label' => $isSetting ? null : self::label($model),
            'changes' => $changes,
        ];
        self::scheduleFlush();
    }

    /** Doğrudan kayıt (giriş, çeviri gibi model olayı olmayan işlemler) */
    public static function write(string $action, ?string $type = null, ?int $id = null, ?string $label = null, array $changes = [], $user = null): void
    {
        $user ??= auth()->user();
        try {
            ActivityLog::query()->create([
                'user_id' => $user?->getKey(),
                'user_name' => $user ? Str::limit(trim($user->first_name . ' ' . $user->last_name) ?: $user->email, 120, '') : null,
                'action' => $action,
                'subject_type' => $type,
                'subject_id' => $id,
                'subject_label' => $label ? Str::limit($label, 255, '') : null,
                'changes' => $changes ?: null,
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
                'url' => Str::limit(request()->fullUrl(), 500, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e); // kayıt hatası işlemi asla bozmasın
        }
    }

    private static function scheduleFlush(): void
    {
        if (self::$flushRegistered) {
            return;
        }
        self::$flushRegistered = true;
        app()->terminating(function () {
            $rows = self::$buffer;
            self::$buffer = [];
            self::$flushRegistered = false;
            foreach ($rows as $row) {
                self::write($row['action'], $row['subject_type'], $row['subject_id'], $row['subject_label'], $row['changes']);
            }
        });
    }

    /** Değişen alanlar: alan => [eski, yeni]; çok dilli alanda sadece değişen diller ("title (en)") */
    private static function diff(Model $model): array
    {
        $out = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, self::HIDDEN, true) || str_ends_with($field, '_hash')) {
                continue;
            }
            $old = $model->getRawOriginal($field);
            $oldArr = is_string($old) ? json_decode($old, true) : null;
            $newArr = is_string($new) ? json_decode($new, true) : null;

            if (is_array($oldArr) || is_array($newArr)) {
                $oldArr = is_array($oldArr) ? $oldArr : [];
                $newArr = is_array($newArr) ? $newArr : [];
                foreach (array_unique([...array_keys($oldArr), ...array_keys($newArr)]) as $key) {
                    $a = $oldArr[$key] ?? null;
                    $b = $newArr[$key] ?? null;
                    if ($a !== $b) {
                        $out["{$field} ({$key})"] = [self::short($a), self::short($b)];
                    }
                }
                continue;
            }
            if ((string) $old !== (string) $new) {
                $out[$field] = [self::short($old), self::short($new)];
            }
        }

        return array_slice($out, 0, 40, true);
    }

    private static function short(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $value))));

        return Str::limit($text, 300);
    }

    /** Kaydın okunur adı */
    public static function label(Model $model): ?string
    {
        foreach (['name', 'title', 'question', 'text', 'subject', 'key', 'page_key', 'slug', 'email', 'code'] as $field) {
            if (!array_key_exists($field, $model->getAttributes())) {
                continue;
            }
            $value = method_exists($model, 'getTranslation') && in_array($field, $model->translatable ?? [], true)
                ? $model->getTranslation($field, 'en', false)
                : $model->getAttribute($field);
            if (is_string($value) && trim($value) !== '') {
                return Str::limit(strip_tags($value), 120);
            }
        }
        if (isset($model->first_name)) {
            return trim($model->first_name . ' ' . $model->last_name);
        }

        return null;
    }

    /** Kaydın paneldeki sayfası (varsa ve hâlâ duruyorsa) */
    public static function url(?string $type, ?int $id): ?string
    {
        $route = self::TYPES[$type][1] ?? null;

        return $route && $id && Route::has($route) ? rescue(fn () => route($route, $id), null, false) : null;
    }
}
