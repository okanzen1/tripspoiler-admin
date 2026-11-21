<?php

use Illuminate\Support\Facades\Schedule;

// Hiçbir kayda bağlı olmayan resim dosyalarını her gece temizle (canlıda cron: * * * * * php artisan schedule:run)
Schedule::command('media:prune')->dailyAt('04:00')->withoutOverlapping();
// Dosyası olmayan resim kayıtlarını boşalt (site boş kutu yerine varsayılan resmi gösterir)
Schedule::command('media:clear-missing')->dailyAt('04:15')->withoutOverlapping();

// Eski kayıtları sil: işlem kayıtları 1 yıl, ziyaret ve partner tıklamaları 6 ay, yardım talepleri 12 ay (modellerin prunable())
Schedule::command('model:prune', ['--model' => [\App\Models\ActivityLog::class, \App\Models\Visit::class, \App\Models\AffiliateClick::class, \App\Models\HelpRequest::class]])->dailyAt('04:30');
// Şifre sıfırlama kayıtları (e-posta içerir) bir saatten fazla tutulmaz
Schedule::command('auth:clear-resets')->hourly();


// Nabız: zamanlayıcı (cron) her dakika çalışıyor mu? Yönetim › Arka plan işleri bunu gösterir
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever('scheduler:last_run', now()->timestamp))->everyMinute()->name('scheduler-heartbeat');
// Menüdeki E-posta rozeti: okunmamış sayısı 5 dakikada bir (sayfaya girmeden de doğru görünsün)
Schedule::command('mailbox:check')->everyFiveMinutes()->withoutOverlapping();

// Claude bağlantısının (OAuth, routes/ai.php) şifreleme anahtarları: güvenlik için git'e gönderilmez; sunucuda yoksa
// zamanlayıcı bir kez oluşturur (yoksa /mcp "Server Error" verir ve Claude bağlanamaz)
Schedule::call(function () {
    if (!file_exists(storage_path('oauth-private.key')) || !file_exists(storage_path('oauth-public.key'))) {
        \Illuminate\Support\Facades\Artisan::call('passport:keys', ['--force' => true]);
    }
    // 13 Kasım 2026'daki bağlantı denemesinden kalan zararsız "probe" kaydı
    \Illuminate\Support\Facades\DB::table('oauth_clients')->where('name', 'probe')->where('redirect_uris', 'like', '%example.invalid%')->delete();
})->everyMinute()->name('passport-keys')->withoutOverlapping();

// Popülerlik: aktivitelerin Wikipedia ilgisi (son 30 gün okunma) her gece güncellenir; site her şehrin en ilgi göreni öne çıkarır
Schedule::command('wiki:refresh')->dailyAt('05:00')->withoutOverlapping();

// Pinterest: yayındaki bloglar sırayla, günde en fazla Yönetim › Pinterest'teki sayı (09–21 arası saat başı, güne yayılır)
Schedule::command('pinterest:send')->hourlyAt(7)->withoutOverlapping(10);

// IndexNow: site haritasındaki yeni / değişen sayfalar saatte bir Bing, Yandex… arama motorlarına bildirilir
Schedule::command('indexnow:sync')->hourlyAt(37)->withoutOverlapping(15);

// Search Console: son 28 günün Google verisi her sabah panele çekilir (Google verisi 2–3 gün geriden gelir)
Schedule::command('gsc:sync')->dailyAt('07:10')->withoutOverlapping(30);

// Sıradaki işler (ör. Diller ve çeviri › toplu çeviri): her dakika işlenir, sırada iş yoksa hemen biter.
// Paylaşımlı sunucuda sürekli çalışan kuyruk işçisi olmadığı için (cron: * * * * * php artisan schedule:run)
// EN SONDA: uzun bir çeviri dakikalarca sürebilir; zamanlayıcı işleri sırayla çalıştırdığı için yukarıdakiler
// (nabız, gece işleri) onu beklemeden önce çalışır. Önceki işçi sürerken yenisi başlamaz (withoutOverlapping).
// Kilit en fazla 20 dk: işçi sunucu tarafından yarıda öldürülürse kilit 24 saat kalıp sırayı dondurmasın
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping(20);
