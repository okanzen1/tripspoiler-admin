<?php

use App\Mcp\Servers\TripSpoilerServer;
use Laravel\Mcp\Facades\Mcp;

// Claude uygulaması (web, masaüstü, telefon): Ayarlar › Bağlayıcılar › "Özel bağlayıcı ekle" →
// https://admin.tripspoiler.com/mcp . İlk bağlanışta panel girişi ve izin ekranı açılır (OAuth, Passport).
// Yalnızca admin bağlanabilir; Claude'un yaptığı her şey İşlem kaydı'nda o kullanıcının adıyla görünür.
Mcp::oauthRoutes();
Mcp::web('/mcp', TripSpoilerServer::class)->middleware(['auth:api', 'role:admin', 'throttle:120,1']);

// Bu bilgisayarda deneme (yerel veritabanı): "php artisan mcp:start tripspoiler"
Mcp::local('tripspoiler', TripSpoilerServer::class);
