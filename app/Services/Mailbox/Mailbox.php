<?php

namespace App\Services\Mailbox;

/**
 * Kurum e-posta hesabına erişim (IMAP). E-postalar panelin veritabanına kopyalanmaz; her işlem doğrudan
 * hesapta yapılır, böylece webmail ile panel her zaman aynı durumu gösterir.
 *
 * @phpstan-type Address array{name: ?string, email: string}
 * @phpstan-type Summary array{uid: int, from: ?Address, to: list<Address>, subject: string, date: ?\Carbon\CarbonImmutable, seen: bool, flagged: bool, answered: bool, has_attachments: bool}
 * @phpstan-type Attachment array{index: int, name: string, mime: string, size: int, content_id: ?string, inline: bool}
 * @phpstan-type Detail array{uid: int, from: ?Address, to: list<Address>, cc: list<Address>, reply_to: list<Address>, subject: string, date: ?\Carbon\CarbonImmutable, seen: bool, flagged: bool, answered: bool, has_attachments: bool, message_id: ?string, references: ?string, html: ?string, text: ?string, attachments: list<Attachment>}
 * @phpstan-type Folder array{path: string, name: string, role: ?string, unseen: int, total: int}
 */
interface Mailbox
{
    /**
     * Klasörler. "role": inbox, sent, drafts, junk, trash, archive veya null (kullanıcı klasörü).
     *
     * @return list<Folder>
     */
    public function folders(): array;

    /**
     * Klasördeki e-postalar (yeniden eskiye). Arama: gönderen, alıcı, konu veya metinde.
     *
     * @return array{items: list<Summary>, total: int}
     */
    public function messages(string $folder, int $page, int $perPage, ?string $search = null): array;

    /**
     * Tek e-posta (gövde ve ekler). Açılan e-posta hesapta da okundu olur.
     *
     * @return Detail|null
     */
    public function message(string $folder, int $uid): ?array;

    /**
     * @return array{name: string, mime: string, content: string}|null
     */
    public function attachment(string $folder, int $uid, int $index): ?array;

    /**
     * @param  list<int>  $uids
     */
    public function setSeen(string $folder, array $uids, bool $seen): void;

    public function setFlagged(string $folder, int $uid, bool $flagged): void;

    public function setAnswered(string $folder, int $uid): void;

    /**
     * @param  list<int>  $uids
     */
    public function move(string $folder, array $uids, string $target): void;

    /**
     * Kalıcı olarak siler (yalnızca Çöp kutusundan çağrılır).
     *
     * @param  list<int>  $uids
     */
    public function delete(string $folder, array $uids): void;

    /**
     * Gönderilen e-postanın kopyasını "Gönderilenler" klasörüne ekler (SMTP otomatik kaydetmez).
     */
    public function appendSent(string $rawMessage): void;

    /**
     * Gelen kutusunda verilen UID'den sonra gelen e-postalar ve bir sonraki UID (yeni e-posta bildirimi için).
     *
     * @return array{next_uid: int, items: list<Summary>}
     */
    public function inboxSince(?int $lastUid): array;
}
