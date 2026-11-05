<?php

namespace App\Services\Mailbox;

/**
 * E-posta gövdesini, panelde betik çalıştıramayan bir iframe içinde gösterilecek tam HTML belgeye çevirir.
 *
 * - Betikler, iframe/form/object gibi etkileşimli öğeler ve olay öznitelikleri (onclick…) çıkarılır;
 *   yanıt başlığındaki CSP yalnızca panelin eklediği tek satırlık (nonce'lu) yükseklik betiğine izin verir.
 *   Bu betik yalnızca belge yüksekliğini panele bildirir; iframe kökeni ayrı olduğu için panele erişemez.
 * - Gömülü görseller (cid:) e-postanın ek adresine çevrilir. Uzak görseller CSP ile varsayılan olarak
 *   engellenir (izleme pikselleri dahil); kullanıcı "görselleri göster" derse izin verilir.
 * - Bağlantılar yeni sekmede açılır.
 */
class MailboxBodyRenderer
{
    /**
     * @param  array{html: ?string, text: ?string, attachments: list<array{index: int, content_id: ?string}>}  $message
     * @param  callable(int): string  $attachmentUrl
     */
    public function document(array $message, callable $attachmentUrl, ?string $nonce = null): string
    {
        $body = filled($message['html'])
            ? $this->sanitize((string) $message['html'], $message['attachments'], $attachmentUrl)
            : '<div class="plain">'.$this->linkify(e((string) $message['text'])).'</div>';

        return '<!doctype html><html><head><meta charset="utf-8"><base target="_blank">'
            .'<style>html{overflow-y:hidden}body{margin:0;padding:16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.5;color:#1e293b;word-wrap:break-word;overflow-wrap:anywhere}'
            .'img{max-width:100%;height:auto}.plain{white-space:pre-wrap}blockquote{margin:0 0 0 8px;padding-left:12px;border-left:3px solid #e2e8f0;color:#64748b}a{color:#2f56b0}</style>'
            .'</head><body>'.$body.($nonce !== null ? $this->heightReporter($nonce) : '').'</body></html>';
    }

    /**
     * iframe yanıtının güvenlik başlığı: betik yok, yalnızca satır içi stil; uzak görsel isteğe bağlı.
     */
    public function contentSecurityPolicy(bool $allowRemoteImages, ?string $nonce = null): string
    {
        return "default-src 'none'; style-src 'unsafe-inline'; font-src data:; img-src 'self' data:".($allowRemoteImages ? ' https: http:' : '')
            .($nonce !== null ? "; script-src 'nonce-{$nonce}'" : '')."; base-uri 'none'; form-action 'none'; frame-ancestors 'self'";
    }

    /**
     * İçerik yüksekliğini panele bildiren betik; panel iframe'i e-postanın boyuna göre uzatır (boş alan kalmaz).
     */
    private function heightReporter(string $nonce): string
    {
        return '<script nonce="'.e($nonce).'">(function(){var r=function(){parent.postMessage({mailboxBodyHeight:Math.ceil(document.documentElement.scrollHeight)},"*")};'
            .'addEventListener("load",r);if(window.ResizeObserver){new ResizeObserver(r).observe(document.body)}'
            .'Array.prototype.forEach.call(document.images,function(i){i.addEventListener("load",r)});r()})();</script>';
    }

    /**
     * @param  list<array{index: int, content_id: ?string}>  $attachments
     * @param  callable(int): string  $attachmentUrl
     */
    private function sanitize(string $html, array $attachments, callable $attachmentUrl): string
    {
        // Yalnızca <body> içeriği alınır.
        if (preg_match('#<body[^>]*>(.*)</body>#is', $html, $match)) {
            $html = $match[1];
        }

        $html = preg_replace('#<(script|iframe|frame|frameset|object|embed|applet|form|meta|link|base)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|iframe|frame|object|embed|applet|form|input|button|textarea|select|meta|link|base)\b[^>]*/?>#is', '', $html) ?? '';
        $html = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html) ?? '';
        $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2#i', '$1="#"', $html) ?? '';

        foreach ($attachments as $attachment) {
            if ($attachment['content_id']) {
                $html = str_ireplace('cid:'.trim($attachment['content_id'], '<>'), $attachmentUrl($attachment['index']), $html);
            }
        }

        return $html;
    }

    private function linkify(string $escapedText): string
    {
        return preg_replace('#\bhttps?://[^\s<]+#i', '<a href="$0" rel="noopener noreferrer">$0</a>', $escapedText) ?? $escapedText;
    }
}
