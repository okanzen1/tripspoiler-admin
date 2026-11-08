<?php

use Illuminate\Support\Facades\Schedule;

// Hiçbir kayda bağlı olmayan resim dosyalarını her gece temizle (canlıda cron: * * * * * php artisan schedule:run)
Schedule::command('media:prune')->dailyAt('04:00')->withoutOverlapping();

// 1 yıldan eski işlem kayıtlarını sil (App\Models\ActivityLog::prunable)
Schedule::command('model:prune', ['--model' => [\App\Models\ActivityLog::class]])->dailyAt('04:30');

// Sıradaki işler (ör. Diller ve çeviri › toplu çeviri): her dakika işlenir, sırada iş yoksa hemen biter.
// Paylaşımlı sunucuda sürekli çalışan kuyruk işçisi olmadığı için (cron: * * * * * php artisan schedule:run)
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')->everyMinute()->withoutOverlapping();
