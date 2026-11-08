<?php

use Illuminate\Support\Facades\Schedule;

// Hiçbir kayda bağlı olmayan resim dosyalarını her gece temizle (canlıda cron: * * * * * php artisan schedule:run)
Schedule::command('media:prune')->dailyAt('04:00')->withoutOverlapping();

// 1 yıldan eski işlem kayıtlarını sil (App\Models\ActivityLog::prunable)
Schedule::command('model:prune', ['--model' => [\App\Models\ActivityLog::class]])->dailyAt('04:30');
