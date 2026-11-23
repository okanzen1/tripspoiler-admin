<?php

namespace App\Console\Commands;

use App\Services\Mailbox\ImapMailbox;
use App\Services\Mailbox\InboxUnreadCount;
use App\Services\Mailbox\Mailbox;
use Illuminate\Console\Command;

/**
 * Menüdeki E-posta rozeti için gelen kutusundaki okunmamış sayısını günceller (zamanlayıcı birkaç dakikada bir çalıştırır).
 * Böylece rozet E-posta sayfasına girmeden de doğru görünür.
 */
class MailboxCheck extends Command
{
    protected $signature = 'mailbox:check';

    protected $description = 'Gelen kutusundaki okunmamış e-posta sayısını menü rozeti için günceller';

    public function handle(Mailbox $mailbox): int
    {
        if (blank(config('mailbox.username')) || blank(config('mailbox.password'))) {
            return self::SUCCESS;
        }
        try {
            ImapMailbox::forgetFolders();
            InboxUnreadCount::remember($mailbox->folders());
            $this->info('Okunmamış: ' . InboxUnreadCount::get());
            $this->notifyNew($mailbox);
        } catch (\Throwable $e) {
            $this->warn('E-posta sunucusuna ulaşılamadı: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }

    /** Son kontrolden sonra gelen e-postalar: herkese panel bildirimi (ilk çalışmada sadece başlangıç noktası kaydedilir) */
    private function notifyNew(Mailbox $mailbox): void
    {
        $last = \Illuminate\Support\Facades\Cache::get('notify:mail_uid');
        $result = $mailbox->inboxSince($last === null ? null : (int) $last);
        \Illuminate\Support\Facades\Cache::forever('notify:mail_uid', max(0, $result['next_uid'] - 1));

        foreach (array_slice($result['items'], -10) as $mail) {
            if ($mail['seen']) {
                continue;
            }
            $from = $mail['from'] ? ($mail['from']['name'] ?: $mail['from']['email']) : __('Bilinmeyen gönderen');
            \App\Support\Notify::everyone('mail', __('Yeni e-posta: :from', ['from' => $from]), $mail['subject'] ?: __('(konu yok)'),
                route('mailbox.show', ['folder' => 'INBOX', 'uid' => $mail['uid']]));
        }
    }
}
