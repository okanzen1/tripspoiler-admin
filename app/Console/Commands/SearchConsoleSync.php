<?php

namespace App\Console\Commands;

use App\Services\SearchConsole;
use Illuminate\Console\Command;

/**
 * Google Search Console'un son 28 günlük verisini panele çeker (Yönetim › Search Console).
 */
class SearchConsoleSync extends Command
{
    protected $signature = 'gsc:sync';

    protected $description = 'Search Console verisini (son 28 gün) günceller';

    public function handle(SearchConsole $gsc): int
    {
        if (!SearchConsole::connected() || !SearchConsole::site()) {
            return self::SUCCESS;
        }
        $result = $gsc->sync();
        $result['error'] ? $this->error($result['error']) : $this->info("{$result['rows']} satır.");

        return self::SUCCESS;
    }
}
