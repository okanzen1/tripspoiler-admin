<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\WikiViews;
use Illuminate\Console\Command;
use Throwable;

/** Wikipedia sayfası olan aktivitelerin son 30 gün okunma sayısını günceller (zamanlayıcı her gün çalıştırır). */
class WikiRefresh extends Command
{
    protected $signature = 'wiki:refresh';

    protected $description = 'Aktivitelerin Wikipedia ilgisini (son 30 gün okunma) günceller';

    public function handle(): int
    {
        $failed = 0;
        Activity::whereNotNull('wiki_title')->orderBy('id')->each(function (Activity $activity) use (&$failed) {
            try {
                WikiViews::refresh($activity);
                $this->line("{$activity->id} {$activity->wiki_title}: " . ($activity->wiki_views ?? '—'));
            } catch (Throwable $e) {
                $failed++;
                $this->warn("{$activity->id}: {$e->getMessage()}");
            }
            usleep(200_000);
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
