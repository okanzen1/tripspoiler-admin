<?php

namespace App\Console\Commands;

use App\Services\Pinterest;
use Illuminate\Console\Command;

/**
 * Yayındaki blogları sırayla Pinterest'e gönderir (günde en fazla Yönetim › Pinterest'teki sayı, gün içine yayılır).
 */
class PinterestSend extends Command
{
    protected $signature = 'pinterest:send';

    protected $description = 'Sıradaki blogları Pinterest\'e gönderir (günlük sınırla)';

    public function handle(Pinterest $pinterest): int
    {
        $count = $pinterest->sendScheduled();
        $this->info("{$count} blog gönderildi.");

        return self::SUCCESS;
    }
}
