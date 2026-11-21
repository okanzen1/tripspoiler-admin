<?php

namespace App\Console\Commands;

use App\Services\IndexNow;
use Illuminate\Console\Command;

/**
 * Site haritasında yeni / değişen sayfaları IndexNow'a (Bing, Yandex…) bildirir.
 */
class IndexNowSync extends Command
{
    protected $signature = 'indexnow:sync';

    protected $description = 'Yeni ve değişen sayfaları IndexNow ile arama motorlarına bildirir';

    public function handle(IndexNow $indexNow): int
    {
        $result = $indexNow->sync();
        $result['error'] ? $this->error($result['error']) : $this->info("{$result['found']} adres, {$result['sent']} gönderildi.");

        return self::SUCCESS;
    }
}
