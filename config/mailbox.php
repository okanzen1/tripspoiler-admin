<?php

/*
|--------------------------------------------------------------------------
| E-posta (panelin posta kutusu)
|--------------------------------------------------------------------------
|
| Panelin E-posta sayfası kurum hesabına IMAP ile bağlanır (okuma, klasörler,
| taşıma, silme) ve SMTP ile gönderir (config/mail.php > mailers.mailbox).
| E-postalar veritabanına kopyalanmaz; her işlem doğrudan hesapta yapılır,
| böylece webmail ile panel her zaman aynı durumu gösterir.
|
*/

return [
    'username' => env('MAILBOX_USERNAME'),
    'password' => env('MAILBOX_PASSWORD'),
    'imap_host' => env('MAILBOX_IMAP_HOST', 'imap.hostinger.com'),
    'imap_port' => (int) env('MAILBOX_IMAP_PORT', 993),
    'from_name' => env('MAILBOX_FROM_NAME', 'TripSpoiler'),
    'per_page' => 25,

    // Gönderirken ek dosya sınırı (dosya başına KB)
    'attachment_max_kb' => 10240,

    // Toplu e-postada tek seferde en fazla alıcı ve elle eklenebilecek adres
    'bulk_limit' => 2000,
    'extra_limit' => 50,
];
