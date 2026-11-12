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
        } catch (\Throwable $e) {
            $this->warn('E-posta sunucusuna ulaşılamadı: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
