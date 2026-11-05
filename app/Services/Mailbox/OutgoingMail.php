<?php

namespace App\Services\Mailbox;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * Panelin E-posta sayfasından gönderilen e-posta (yeni, yanıt veya iletme). Metin düz yazıdır;
 * HTML sürümü aynı metnin güvenli (kaçışlanmış) halidir.
 */
class OutgoingMail extends Mailable
{
    use Queueable;

    /**
     * @param  list<string>  $toAddresses
     * @param  list<string>  $ccAddresses
     * @param  list<string>  $bccAddresses
     * @param  list<array{name: string, mime: string, content: string}>  $files
     */
    public function __construct(
        public array $toAddresses,
        public array $ccAddresses,
        public array $bccAddresses,
        public string $subjectLine,
        public string $bodyText,
        public array $files = [],
        public ?string $inReplyTo = null,
        public ?string $references = null,
        public ?string $listUnsubscribe = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('mailbox.username'), (string) config('mailbox.from_name')),
            to: $this->toAddresses,
            cc: $this->ccAddresses,
            bcc: $this->bccAddresses,
            subject: $this->subjectLine,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            references: $this->references ? preg_split('/\s+/', trim($this->references)) : [],
            text: array_filter([
                'In-Reply-To' => $this->inReplyTo,
                'List-Unsubscribe' => $this->listUnsubscribe ? '<'.$this->listUnsubscribe.'>' : null,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;white-space:pre-wrap">'.e($this->bodyText).'</div>',
            text: 'mail.mailbox-plain',
            with: ['body' => $this->bodyText],
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (array $file) => Attachment::fromData(fn () => $file['content'], $file['name'])->withMime($file['mime']),
            $this->files,
        );
    }
}
