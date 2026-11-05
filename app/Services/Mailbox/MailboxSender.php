<?php

namespace App\Services\Mailbox;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-postayı kurum hesabının SMTP sunucusundan gönderir ve kopyasını IMAP'te "Gönderilenler" klasörüne ekler.
 */
class MailboxSender
{
    public function __construct(private Mailbox $mailbox) {}

    public function send(OutgoingMail $mail): void
    {
        try {
            $sent = Mail::mailer('mailbox')->send($mail);
        } catch (Throwable $exception) {
            Log::warning('Panel e-postası gönderilemedi.', ['error' => $exception->getMessage()]);

            throw new MailboxUnavailable('E-posta gönderilemedi.', previous: $exception);
        }

        // Kopyanın eklenememesi gönderimi geçersiz kılmaz; yalnızca loglanır.
        try {
            if ($sent !== null) {
                $this->mailbox->appendSent($sent->getSymfonySentMessage()->toString());
            }
        } catch (Throwable $exception) {
            Log::warning('Gönderilen e-postanın kopyası "Gönderilenler" klasörüne eklenemedi.', ['error' => $exception->getMessage()]);
        }
    }
}
