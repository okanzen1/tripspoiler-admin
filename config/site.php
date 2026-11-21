<?php

return [
    // Sitenin adresi (paneldeki "Siteyi aç" bağlantıları)
    'url' => rtrim(env('SITE_URL', 'https://tripspoiler.com'), '/'),

    // IndexNow (Bing, Yandex…): yeni / değişen sayfaları anında bildirir (App\Services\IndexNow). Anahtar herkese
    // açıktır; aynısı ön yüzde public/<anahtar>.txt olarak durur, arama motoru sitenin sahibini onunla doğrular.
    'indexnow_key' => env('INDEXNOW_KEY', '263054b4959e478264565b1ef80c7ea2'),

    // Front'taki sayfa adresleri. Slug her dilde aynıdır (çevrilmez); dil sadece öndeki önektir.
    // Slug değişince eski adres için otomatik yönlendirme (redirects tablosu) bu kalıplarla yazılır.
    'paths' => [
        'activity' => '/{locale}/activities/{slug}',
        'blog' => '/{locale}/blog/{slug}',
        'city' => '/{locale}/cities/{slug}',
        'attraction' => '/{locale}/attractions/{slug}',
    ],
];
