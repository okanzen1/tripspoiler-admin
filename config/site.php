<?php

return [
    // Sitenin adresi (paneldeki "Siteyi aç" bağlantıları)
    'url' => rtrim(env('SITE_URL', 'https://tripspoiler.com'), '/'),

    // Front'taki sayfa adresleri. Slug her dilde aynıdır (çevrilmez); dil sadece öndeki önektir.
    // Slug değişince eski adres için otomatik yönlendirme (redirects tablosu) bu kalıplarla yazılır.
    'paths' => [
        'activity' => '/{locale}/activities/{slug}',
        'blog' => '/{locale}/blog/{slug}',
        'city' => '/{locale}/cities/{slug}',
    ],
];
