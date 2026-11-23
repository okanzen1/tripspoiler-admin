<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Panel bildirimi (sağ üstteki zil ve Bildirimler sayfası). Her kullanıcıya ayrı satır yazılır: biri okuyunca
 * diğerinde okunmamış kalır. Türler ve simgeleri: App\Support\Notify::KINDS.
 */
class PanelNotice extends Notification
{
    public function __construct(
        public string $kind,
        public string $title,
        public ?string $body = null,
        public ?string $url = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string|null> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => $this->kind, 'title' => $this->title, 'body' => $this->body, 'url' => $this->url];
    }
}
