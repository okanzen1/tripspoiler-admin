<?php

namespace App\Support;

use App\Jobs\TranslateModel;
use App\Services\Translation\ModelTranslator;
use Illuminate\Support\Facades\Cache;

/**
 * Toplu çeviri ("Tümünü sırayla çevir", Diller sayfasındaki toplu çeviri): kayıtları sıraya alır, kaç kaydın bittiğini
 * sayar ve sıra boşalınca başlatan kişilere "Toplu çeviri bitti" bildirimi gönderir.
 */
class BulkTranslation
{
    private const USERS = 'bulk-translation:users';

    private const DONE = 'bulk-translation:done';

    private const FAILED = 'bulk-translation:failed';

    /**
     * Kayıtları toplu çeviri sırasına alır. Hangi dillerin eksik olduğu iş çalışırken hesaplanır; burada ağır hesap
     * yapılmaz (istek hemen döner).
     *
     * @param  list<string>  $keys  "tür:id" (ör. "blog:12")
     * @param  string|null  $locale  yalnızca bu dil; null: eksik / eskimiş bütün diller
     * @return int sıraya alınan kayıt sayısı
     */
    public static function queue(array $keys, ?string $locale, ?int $userId): int
    {
        $ids = [];
        foreach (array_unique($keys) as $key) {
            [$type, $id] = array_pad(explode(':', (string) $key, 2), 2, null);
            if (isset(ModelTranslator::TYPES[$type]) && ctype_digit((string) $id)) {
                $ids[$type][] = (int) $id;
            }
        }

        $count = 0;
        foreach ($ids as $type => $list) {
            // sadece numara: iş çalışırken kaydın güncel hali okunur
            foreach (ModelTranslator::TYPES[$type]::query()->select('id')->whereIn('id', $list)->orderBy('id')->get() as $model) {
                dispatch(new TranslateModel($model, $locale ? [$locale] : null));
                $count++;
            }
        }
        if ($count && $userId) {
            Cache::forever(self::USERS, array_values(array_unique([...Cache::get(self::USERS, []), $userId])));
        }

        return $count;
    }

    /** Çevirisi eksik bütün kayıtlar (panelin hazır listesinden), istenirse tek dil için */
    public static function pendingKeys(?string $locale = null): array
    {
        return TranslationTodo::items()
            ->filter(fn ($item) => $locale === null || in_array($locale, $item['missing'] ?? [$locale], true))
            ->map(fn ($item) => $item['type'] . ':' . $item['id'])
            ->values()->all();
    }

    public static function recordDone(): void
    {
        Cache::increment(self::DONE);
        self::finishIfEmpty();
    }

    public static function recordFailed(): void
    {
        Cache::increment(self::FAILED);
        self::finishIfEmpty();
    }

    /** Toplu sırada iş kalmadıysa: başlatanlara bildirim, sayaçlar sıfırlanır */
    public static function finishIfEmpty(): void
    {
        // Bu iş hâlâ tabloda (çalışırken silinmez) olabilir: 1'e kadar boş sayılır
        if (Queues::bulkCount() > 1) {
            return;
        }
        $users = Cache::pull(self::USERS, []);
        $done = (int) Cache::pull(self::DONE, 0);
        $failed = (int) Cache::pull(self::FAILED, 0);
        if (!$users) {
            return; // başlatan bilinmiyor (ör. eski sıradan kalan iş): sadece sayaçlar sıfırlandı
        }
        $body = $failed
            ? __(':done kayıt çevrildi, :failed kayıt çevrilemedi (Yönetim › Arka plan işleri).', ['done' => $done, 'failed' => $failed])
            : __(':done kayıt bütün dillere çevrildi.', ['done' => $done]);
        $kind = $failed ? 'translation_failed' : 'translation';
        collect($users)->each(fn ($id) => Notify::user((int) $id, $kind, __('Toplu çeviri bitti'), $body, route('translations.index')));
    }
}
