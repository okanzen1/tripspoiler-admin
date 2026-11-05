<?php

namespace App\Services\Mailbox;

use Illuminate\Support\Facades\Cache;

/**
 * Gelen kutusundaki okunmamış e-posta sayısı (menüdeki E-posta rozeti). Sayı, klasörler her yüklendiğinde
 * (dakikalık "mailbox:check" ve E-posta sayfası) önbelleğe yazılır; menü posta sunucusuna bağlanmadan okur.
 */
class InboxUnreadCount
{
    public const CACHE_KEY = 'mailbox:inbox_unseen';

    /**
     * @param  list<array{path: string, name: string, role: ?string, unseen: int, total: int}>  $folders
     */
    public static function remember(array $folders): void
    {
        $inbox = collect($folders)->first(fn (array $folder) => strtoupper($folder['path']) === 'INBOX');

        if ($inbox !== null) {
            Cache::forever(self::CACHE_KEY, (int) $inbox['unseen']);
        }
    }

    public static function get(): int
    {
        return (int) Cache::get(self::CACHE_KEY, 0);
    }
}
