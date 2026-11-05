<?php

namespace App\Services\Mailbox;

use App\Models\BlogSubscriber;

/**
 * Toplu e-postanın tek alıcıya giden halini hazırlar. Aboneye gidenlerin altına "abonelikten çık" bağlantısı
 * ve List-Unsubscribe başlığı eklenir (toplu duyurularda yasal gereklilik); elle eklenen adreslere eklenmez.
 */
class BulkEmailComposer
{
    public function forSubscriber(BlogSubscriber $subscriber, string $subject, string $body): OutgoingMail
    {
        $unsubscribe = $subscriber->unsubscribeUrl();

        return $this->make(
            (string) $subscriber->email,
            $subject,
            rtrim($body) . "\n\n—\nBu e-postaları almak istemiyorsanız aboneliğinizi buradan iptal edebilirsiniz: {$unsubscribe}",
            $unsubscribe,
        );
    }

    public function forAddress(string $address, string $subject, string $body): OutgoingMail
    {
        return $this->make($address, $subject, $body);
    }

    private function make(string $to, string $subject, string $body, ?string $unsubscribe = null): OutgoingMail
    {
        return new OutgoingMail(
            toAddresses: [$to],
            ccAddresses: [],
            bccAddresses: [],
            subjectLine: $subject,
            bodyText: ltrim($body, "\n"),
            listUnsubscribe: $unsubscribe,
        );
    }
}
