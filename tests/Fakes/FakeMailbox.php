<?php

namespace Tests\Fakes;

use App\Services\Mailbox\Mailbox;
use App\Services\Mailbox\MailboxUnavailable;
use Carbon\CarbonImmutable;

/**
 * Testlerde gerçek IMAP sunucusu yerine bellekte çalışan posta kutusu.
 */
class FakeMailbox implements Mailbox
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public array $messages = [];

    public array $appended = [];

    public bool $down = false;

    public function __construct()
    {
        $this->messages = ['INBOX' => [], 'INBOX.Sent' => [], 'INBOX.Trash' => []];
    }

    public function add(string $folder, int $uid, array $overrides = []): void
    {
        $this->messages[$folder][$uid] = $overrides + [
            'uid' => $uid,
            'from' => ['name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com'],
            'to' => [['name' => null, 'email' => 'info@tripspoiler.com']],
            'cc' => [],
            'reply_to' => [],
            'subject' => "Mesaj {$uid}",
            'date' => CarbonImmutable::parse('2026-11-01 10:00'),
            'seen' => false,
            'flagged' => false,
            'answered' => false,
            'has_attachments' => false,
            'message_id' => "<{$uid}@example.com>",
            'references' => null,
            'html' => null,
            'text' => "Merhaba, bu {$uid} numaralı mesaj.",
            'attachments' => [],
        ];
    }

    public function folders(): array
    {
        $this->guard();

        return collect($this->messages)->map(fn ($items, $path) => [
            'path' => $path,
            'name' => str_replace('INBOX.', '', $path),
            'role' => match ($path) { 'INBOX' => 'inbox', 'INBOX.Sent' => 'sent', 'INBOX.Trash' => 'trash', default => null },
            'unseen' => collect($items)->where('seen', false)->count(),
            'total' => count($items),
        ])->values()->all();
    }

    public function messages(string $folder, int $page, int $perPage, ?string $search = null): array
    {
        $this->guard();
        $items = collect($this->messages[$folder] ?? [])
            ->when($search, fn ($c) => $c->filter(fn ($m) => str_contains(mb_strtolower($m['subject'] . ' ' . $m['text']), mb_strtolower($search))))
            ->sortKeysDesc()->values();

        return ['items' => $items->forPage($page, $perPage)->values()->all(), 'total' => $items->count()];
    }

    public function message(string $folder, int $uid): ?array
    {
        $this->guard();
        if (!isset($this->messages[$folder][$uid])) {
            return null;
        }
        $this->messages[$folder][$uid]['seen'] = true;

        return $this->messages[$folder][$uid];
    }

    public function attachment(string $folder, int $uid, int $index): ?array
    {
        return null;
    }

    public function setSeen(string $folder, array $uids, bool $seen): void
    {
        foreach ($uids as $uid) {
            $this->messages[$folder][$uid]['seen'] = $seen;
        }
    }

    public function setFlagged(string $folder, int $uid, bool $flagged): void
    {
        $this->messages[$folder][$uid]['flagged'] = $flagged;
    }

    public function setAnswered(string $folder, int $uid): void
    {
        $this->messages[$folder][$uid]['answered'] = true;
    }

    public function move(string $folder, array $uids, string $target): void
    {
        foreach ($uids as $uid) {
            $this->messages[$target][$uid] = $this->messages[$folder][$uid];
            unset($this->messages[$folder][$uid]);
        }
    }

    public function delete(string $folder, array $uids): void
    {
        foreach ($uids as $uid) {
            unset($this->messages[$folder][$uid]);
        }
    }

    public function appendSent(string $rawMessage): void
    {
        $this->appended[] = $rawMessage;
    }

    public function inboxSince(?int $lastUid): array
    {
        return ['next_uid' => 1, 'items' => []];
    }

    private function guard(): void
    {
        if ($this->down) {
            throw new MailboxUnavailable('Sunucu kapalı');
        }
    }
}
