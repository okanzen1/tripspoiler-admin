<?php

use App\Mcp\Servers\TripSpoilerServer;
use Laravel\Mcp\Facades\Mcp;

// Claude uygulaması (web, masaüstü, telefon): Ayarlar › Bağlayıcılar › "Özel bağlayıcı ekle" →
// https://admin.tripspoiler.com/mcp . İlk bağlanışta panel girişi ve izin ekranı açılır (OAuth, Passport).
// Yalnızca admin bağlanabilir; Claude'un yaptığı her şey İşlem kaydı'nda o kullanıcının adıyla görünür.
Mcp::oauthRoutes();
Mcp::web('/mcp', TripSpoilerServer::class)->middleware(['auth:api', 'role:admin', 'throttle:120,1']);

// HERKESE AÇIK site bağlantısı: yapay zekâ asistanları sitede yayında olanı arar (sadece okur, giriş yok).
// Tanıtım kartı ön yüzde: https://tripspoiler.com/.well-known/mcp/server-card.json
Mcp::web('/mcp/site', \App\Mcp\Site\SiteServer::class)->middleware(['throttle:60,1']);

// Bu bilgisayarda deneme (yerel veritabanı): "php artisan mcp:start tripspoiler"
Mcp::local('tripspoiler', TripSpoilerServer::class);
