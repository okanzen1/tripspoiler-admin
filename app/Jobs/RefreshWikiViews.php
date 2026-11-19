<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Services\WikiViews;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Wikipedia sayfası değişen aktivitenin sayısı kayıttan hemen sonra alınır (her gece de wiki:refresh). */
class RefreshWikiViews implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $activityId) {}

    public function handle(): void
    {
        if ($activity = Activity::find($this->activityId)) {
            WikiViews::refresh($activity);
        }
    }
}
