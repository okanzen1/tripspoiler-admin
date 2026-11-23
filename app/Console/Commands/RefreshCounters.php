<?php

namespace App\Console\Commands;

use App\Support\ContentTodo;
use App\Support\TranslationTodo;
use Illuminate\Console\Command;

/**
 * Menüdeki "çevirisi eksik" ve "SEO'su eksik" sayılarını arka planda yeniler (bir kayıt değiştiyse). Panel sayfaları
 * bu hesaplamayı hiç beklemez, hazır sayıyı okur (App\Support\StaleCache). Zamanlayıcı dakikada bir çalıştırır.
 */
class RefreshCounters extends Command
{
    protected $signature = 'panel:refresh-counters {--force : değişiklik olmasa da yeniden hesapla}';

    protected $description = 'Menüdeki eksik çeviri / eksik SEO sayılarını arka planda yeniler';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $this->line('Çeviri listesi: ' . (TranslationTodo::refresh($force) ? 'yenilendi' : 'güncel'));
        $this->line('İçerik listesi: ' . (ContentTodo::refresh($force) ? 'yenilendi' : 'güncel'));

        return self::SUCCESS;
    }
}
