<?php

namespace App\Jobs;

use App\Models\BlogSubscriber;
use App\Services\Mailbox\BulkEmailComposer;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Toplu e-postanın tek alıcıya gönderimi: ya bir blog abonesi ya da elle eklenmiş bir adres.
 * Her alıcıya ayrı e-posta gider; alıcılar birbirini görmez. Kuyrukta şifreli bekler.
 */
class SendBulkEmail implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 120;

    public function __construct(
        public string $subject,
        public string $body,
        public ?int $subscriberId = null,
        public ?string $address = null,
    ) {}

    public function handle(BulkEmailComposer $composer): void
    {
        if ($this->subscriberId !== null) {
            $subscriber = BlogSubscriber::find($this->subscriberId);

            // Kuyrukta beklerken abonelikten çıktıysa gönderilmez
            if ($subscriber === null || !$subscriber->isActive()) {
                return;
            }

            $mail = $composer->forSubscriber($subscriber, $this->subject, $this->body);
        } else {
            $mail = $composer->forAddress((string) $this->address, $this->subject, $this->body);
        }

        Mail::mailer('mailbox')->send($mail);
    }
}
