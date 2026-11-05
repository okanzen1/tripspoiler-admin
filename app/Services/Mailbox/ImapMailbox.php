<?php

namespace App\Services\Mailbox;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Header;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

/**
 * Kurum e-posta hesabına IMAP ile bağlantı (webklex/php-imap; PHP'nin imap eklentisi gerekmez).
 *
 * Bağlantı istek başına bir kez açılır. Liste görünümünde gövde indirilmez ve e-postalar okundu yapılmaz
 * (yalnızca açılan e-posta okundu olur, webmail'deki gibi). Klasör listesi ve sayıları kısa süre önbellekte
 * tutulur; okundu/taşıma/silme/gönderme gibi sayıları değiştiren her işlemde önbellek temizlenir.
 */
class ImapMailbox implements Mailbox
{
    public const FOLDERS_CACHE_KEY = 'mailbox:folders';

    private const FOLDERS_CACHE_SECONDS = 60;

    private ?Client $client = null;

    /** @var array<string, Folder>|null */
    private ?array $folderObjects = null;

    /**
     * Klasör adlarından rol tahmini (Hostinger/Dovecot: INBOX.Sent, INBOX.Trash … veya Sent, Trash …).
     */
    private const ROLE_PATTERNS = [
        'sent' => '/(^|[.\/])(sent|sent items|sent messages|gönderilen(ler)?|gonderilen(ler)?)$/iu',
        'drafts' => '/(^|[.\/])(drafts?|taslak(lar)?)$/iu',
        'junk' => '/(^|[.\/])(junk|spam|bulk|gereksiz|istenmeyen)$/iu',
        'trash' => '/(^|[.\/])(trash|deleted|deleted items|deleted messages|bin|çöp|cop|çöp kutusu)$/iu',
        'archive' => '/(^|[.\/])(archive|archives|arşiv|arsiv)$/iu',
    ];

    public function folders(): array
    {
        return Cache::remember(self::FOLDERS_CACHE_KEY, self::FOLDERS_CACHE_SECONDS, fn () => $this->loadFolders());
    }

    public static function forgetFolders(): void
    {
        Cache::forget(self::FOLDERS_CACHE_KEY);
    }

    /**
     * @return list<array{path: string, name: string, role: ?string, unseen: int, total: int}>
     */
    private function loadFolders(): array
    {
        $folders = [];

        foreach ($this->folderObjects() as $folder) {
            $status = $this->status($folder);

            $folders[] = [
                'path' => $folder->path,
                'name' => $folder->name,
                'role' => $this->role($folder->path),
                'unseen' => (int) ($status['unseen'] ?? 0),
                'total' => (int) ($status['messages'] ?? $status['exists'] ?? 0),
            ];
        }

        return $folders;
    }

    /**
     * Liste, kütüphanenin Message nesneleri yerine doğrudan üç IMAP komutuyla (arama, bayraklar, başlıklar) çekilir.
     * Message nesnesi her okunmamış e-posta için sunucuya ayrıca "okunmadı" komutu gönderdiğinden liste çok yavaşlıyordu.
     */
    public function messages(string $folder, int $page, int $perPage, ?string $search = null): array
    {
        $query = $this->folder($folder)->query();
        $uids = (filled($search) ? $query->text((string) $search) : $query->all())->search()
            ->map(fn ($uid) => (int) $uid)->sortDesc()->values();

        return [
            'items' => $this->summaries($folder, $uids->forPage($page, $perPage)->values()->all()),
            'total' => $uids->count(),
        ];
    }

    public function message(string $folder, int $uid): ?array
    {
        $message = $this->find($folder, $uid);

        if ($message === null) {
            return null;
        }

        if (! $this->hasFlag($message, 'seen')) {
            $message->setFlag('Seen');
            self::forgetFolders();
        }

        $attachments = [];
        foreach ($message->getAttachments()->values() as $index => $attachment) {
            $attachments[] = [
                'index' => $index,
                'name' => (string) ($attachment->getName() ?: 'ek-'.($index + 1)),
                'mime' => (string) ($attachment->getMimeType() ?: 'application/octet-stream'),
                'size' => (int) $attachment->getSize(),
                'content_id' => $attachment->getId() ? (string) $attachment->getId() : null,
                'inline' => strtolower((string) $attachment->getDisposition()) === 'inline',
            ];
        }

        return [
            ...$this->summary($message),
            'seen' => true,
            'cc' => $this->addresses($message->getCc()),
            'reply_to' => $this->addresses($message->getReplyTo()),
            'message_id' => $this->messageIds($message->getMessageId()),
            'references' => $this->messageIds($message->getReferences()),
            'html' => $message->hasHTMLBody() ? $message->getHTMLBody() : null,
            'text' => $message->hasTextBody() ? $message->getTextBody() : null,
            'attachments' => $attachments,
        ];
    }

    public function attachment(string $folder, int $uid, int $index): ?array
    {
        $attachment = $this->find($folder, $uid, leaveUnread: true)?->getAttachments()->values()->get($index);

        return $attachment === null ? null : [
            'name' => (string) ($attachment->getName() ?: 'ek-'.($index + 1)),
            'mime' => (string) ($attachment->getMimeType() ?: 'application/octet-stream'),
            'content' => (string) $attachment->getContent(),
        ];
    }

    public function setSeen(string $folder, array $uids, bool $seen): void
    {
        foreach ($uids as $uid) {
            $message = $this->find($folder, $uid, leaveUnread: true);
            $seen ? $message?->setFlag('Seen') : $message?->unsetFlag('Seen');
        }

        self::forgetFolders();
    }

    public function setFlagged(string $folder, int $uid, bool $flagged): void
    {
        $message = $this->find($folder, $uid, leaveUnread: true);
        $flagged ? $message?->setFlag('Flagged') : $message?->unsetFlag('Flagged');
    }

    public function setAnswered(string $folder, int $uid): void
    {
        $this->find($folder, $uid, leaveUnread: true)?->setFlag('Answered');
    }

    public function move(string $folder, array $uids, string $target): void
    {
        foreach ($uids as $uid) {
            $this->find($folder, $uid, leaveUnread: true)?->move($target, true);
        }

        self::forgetFolders();
    }

    public function delete(string $folder, array $uids): void
    {
        foreach ($uids as $uid) {
            $this->find($folder, $uid, leaveUnread: true)?->delete(true);
        }

        self::forgetFolders();
    }

    public function appendSent(string $rawMessage): void
    {
        $sent = collect($this->folders())->firstWhere('role', 'sent');

        if ($sent !== null) {
            $this->folder($sent['path'])->appendMessage($rawMessage, ['\\Seen'], now()->format('d-M-Y H:i:s O'));
            self::forgetFolders();
        }
    }

    public function inboxSince(?int $lastUid): array
    {
        $inbox = $this->folder('INBOX');
        $status = $this->status($inbox);
        $nextUid = (int) ($status['uidnext'] ?? 1);

        if ($lastUid === null || $nextUid <= $lastUid + 1) {
            return ['next_uid' => $nextUid, 'items' => []];
        }

        $uids = $inbox->query()->whereUid(($lastUid + 1).':*')->search()
            ->map(fn ($uid) => (int) $uid)->filter(fn (int $uid) => $uid > $lastUid)->sort()->values()->all();

        return [
            'next_uid' => $nextUid,
            'items' => $this->summaries('INBOX', $uids),
        ];
    }

    private function client(): Client
    {
        if (! class_exists(ClientManager::class)) {
            throw new MailboxUnavailable('webklex/php-imap kurulu değil.');
        }

        if ($this->client === null) {
            try {
                $this->client = (new ClientManager)->make([
                    'host' => config('mailbox.imap_host'),
                    'port' => config('mailbox.imap_port'),
                    'encryption' => 'ssl',
                    'validate_cert' => true,
                    'username' => config('mailbox.username'),
                    'password' => config('mailbox.password'),
                    'protocol' => 'imap',
                    'timeout' => 20,
                ]);
                $this->client->connect();
            } catch (Throwable $exception) {
                $this->client = null;

                throw new MailboxUnavailable('E-posta sunucusuna bağlanılamadı.', previous: $exception);
            }
        }

        return $this->client;
    }

    /**
     * Klasörler istek başına bir kez listelenir (her işlemde sunucuya yeniden sorulmaz).
     *
     * @return array<string, Folder>
     */
    private function folderObjects(): array
    {
        if ($this->folderObjects === null) {
            $this->folderObjects = [];

            foreach ($this->client()->getFolders(false) as $folder) {
                /** @var Folder $folder */
                $this->folderObjects[$folder->path] = $folder;
            }
        }

        return $this->folderObjects;
    }

    private function folder(string $path): Folder
    {
        $folder = $this->folderObjects()[$path] ?? null;

        if ($folder === null) {
            throw new MailboxUnavailable("Klasör bulunamadı: {$path}");
        }

        return $folder;
    }

    private function find(string $folder, int $uid, bool $leaveUnread = false): ?Message
    {
        try {
            $query = $this->folder($folder)->query();

            if ($leaveUnread) {
                $query->leaveUnread();
            }

            return $query->getMessageByUid($uid);
        } catch (MailboxUnavailable $exception) {
            throw $exception;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function status(Folder $folder): array
    {
        try {
            return array_change_key_case((array) $folder->status(), CASE_LOWER);
        } catch (Throwable) {
            return [];
        }
    }

    private function role(string $path): ?string
    {
        if (strcasecmp($path, 'INBOX') === 0) {
            return 'inbox';
        }

        foreach (self::ROLE_PATTERNS as $role => $pattern) {
            if (preg_match($pattern, $path) === 1) {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return array{uid: int, from: ?array{name: ?string, email: string}, to: list<array{name: ?string, email: string}>, subject: string, date: ?CarbonImmutable, seen: bool, flagged: bool, answered: bool, has_attachments: bool}
     */
    private function summary(Message $message): array
    {
        return $this->summaryFromHeader((int) $message->getUid(), $message->getHeader(), $this->flags($message), $message->hasAttachments());
    }

    /**
     * Verilen UID'lerin özetleri (sıra korunur): bayraklar ve başlıklar tek seferde çekilir, e-postaya dokunulmaz.
     *
     * @param  list<int>  $uids
     * @return list<array<string, mixed>>
     */
    private function summaries(string $folder, array $uids): array
    {
        if ($uids === []) {
            return [];
        }

        $client = $this->client();
        $client->openFolder($this->folder($folder)->path);
        $connection = $client->getConnection();
        $flags = $connection->flags($uids, IMAP::ST_UID)->validatedData();
        $headers = $connection->headers($uids, 'RFC822', IMAP::ST_UID)->validatedData();

        $items = [];
        foreach ($uids as $uid) {
            if (! isset($headers[$uid])) {
                continue;
            }

            $header = new Header((string) $headers[$uid], $client->getConfig());
            $messageFlags = collect((array) ($flags[$uid] ?? []))->map(fn ($flag) => strtolower(ltrim((string) $flag, '\\')))->values();
            // Ek bilgisi gövde yapısı indirilmeden içerik türünden tahmin edilir (karışık içerik = ek var).
            $hasAttachments = str_contains(strtolower((string) $header->get('content_type')), 'multipart/mixed');

            $items[] = $this->summaryFromHeader($uid, $header, $messageFlags, $hasAttachments);
        }

        return $items;
    }

    /**
     * @param  Collection<int, string>  $flags
     * @return array{uid: int, from: ?array{name: ?string, email: string}, to: list<array{name: ?string, email: string}>, subject: string, date: ?CarbonImmutable, seen: bool, flagged: bool, answered: bool, has_attachments: bool}
     */
    private function summaryFromHeader(int $uid, ?Header $header, Collection $flags, bool $hasAttachments): array
    {
        return [
            'uid' => $uid,
            'from' => $this->addresses($header?->get('from'))[0] ?? null,
            'to' => $this->addresses($header?->get('to')),
            'subject' => $this->decode((string) $header?->get('subject')),
            'date' => $this->date($header),
            'seen' => $flags->contains('seen'),
            'flagged' => $flags->contains('flagged'),
            'answered' => $flags->contains('answered'),
            'has_attachments' => $hasAttachments,
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function flags(Message $message): Collection
    {
        return collect($message->getFlags()->toArray())->map(fn ($flag) => strtolower(ltrim((string) $flag, '\\')))->values();
    }

    private function hasFlag(Message $message, string $flag): bool
    {
        return $this->flags($message)->contains($flag);
    }

    /**
     * @return list<array{name: ?string, email: string}>
     */
    private function addresses(mixed $attribute): array
    {
        $items = $attribute === null ? [] : (method_exists($attribute, 'toArray') ? $attribute->toArray() : (array) $attribute);

        return array_values(array_filter(array_map(fn ($address) => is_object($address) && filled($address->mail ?? null)
            ? ['name' => filled($address->personal ?? null) ? (trim($this->decode((string) $address->personal), "\"' ") ?: null) : null, 'email' => (string) $address->mail]
            : null, $items)));
    }

    /**
     * Message-ID / References değerlerini "<id@alan>" biçimine getirir (kütüphane köşeli parantezleri atabiliyor).
     */
    private function messageIds(mixed $attribute): ?string
    {
        $values = $attribute === null ? [] : (method_exists($attribute, 'toArray') ? $attribute->toArray() : [(string) $attribute]);
        $ids = collect($values)
            ->flatMap(fn ($value) => preg_split('/[\s,]+/', (string) $value) ?: [])
            ->map(fn (string $id) => trim($id, " <>\t"))
            ->filter()
            ->map(fn (string $id) => '<'.$id.'>')
            ->unique();

        return $ids->isEmpty() ? null : $ids->implode(' ');
    }

    /**
     * Kütüphanenin çözmeden bıraktığı MIME kodlu başlıkları (=?utf-8?Q?...?=) çözer.
     */
    private function decode(string $value): string
    {
        if (str_contains($value, '=?')) {
            $value = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $value;
        }

        return trim($value);
    }

    private function date(?Header $header): ?CarbonImmutable
    {
        try {
            $date = $header?->get('date')?->toDate();

            return $date ? CarbonImmutable::instance($date)->setTimezone(config('app.timezone')) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
