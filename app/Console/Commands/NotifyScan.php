<?php

namespace App\Console\Commands;

use App\Models\BlogSubscriber;
use App\Models\HelpRequest;
use App\Models\Review;
use App\Support\Notify;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Siteden (ön yüz) gelen yeni kayıtlar için panel bildirimi: yardım talebi, ziyaretçi yorumu, blog abonesi ve
 * başarısız arka plan işleri. Ön yüz ayrı projedir ve aynı veritabanına yazar; panel bu yüzden dakikada bir son
 * gördüğü kayıt numarasından sonrasına bakar. İlk çalışmada sadece başlangıç noktası kaydedilir (eskiler bildirilmez).
 */
class NotifyScan extends Command
{
    protected $signature = 'notify:scan';

    protected $description = 'Yeni yardım talebi, yorum, abone ve başarısız işler için panel bildirimi';

    public function handle(): int
    {
        $this->scan('help', HelpRequest::query(), function (HelpRequest $help) {
            Notify::everyone('help', __('Yeni yardım talebi: :name', ['name' => $help->name ?: $help->email]), $help->reference, route('help-requests.show', $help));
        });

        // Ziyaretçinin bıraktığı, onay bekleyen yorumlar (panelden eklenenler zaten onaylı girilir)
        $this->scan('review', Review::where('approved', false)->with('activity'), function (Review $review) {
            Notify::everyone('review', __('Yeni yorum (:rating★): :name', ['rating' => $review->rating, 'name' => $review->name]),
                trim(($review->activity?->name ? $review->activity->name . ' — ' : '') . \Illuminate\Support\Str::limit((string) $review->comment, 140)),
                route('reviews.edit', $review));
        });

        $this->scan('subscriber', BlogSubscriber::query(), function (BlogSubscriber $subscriber) {
            Notify::everyone('subscriber', __('Yeni blog abonesi'), $subscriber->email, route('subscribers.index'));
        });

        $this->failedJobs();

        return self::SUCCESS;
    }

    /** Son görülen numaradan sonraki kayıtlar (en fazla 20; arada çok birikirse tek tek boğmasın) */
    private function scan(string $name, $query, \Closure $notify): void
    {
        $key = "notify:last:{$name}";
        $last = Cache::get($key);
        $max = (int) (clone $query)->getModel()->newQuery()->max('id');
        if ($last === null) {
            Cache::forever($key, $max);

            return;
        }
        $items = (clone $query)->where('id', '>', $last)->orderBy('id')->limit(20)->get();
        Cache::forever($key, max((int) $last, $max));
        $items->each(fn ($item) => rescue(fn () => $notify($item)));
    }

    /** Başarısız arka plan işleri: adminlere. Çeviri hataları zaten işi başlatana bildirilir, burada sayılmaz. */
    private function failedJobs(): void
    {
        $key = 'notify:last:failed_job';
        $last = Cache::get($key);
        $max = (int) DB::table('failed_jobs')->max('id');
        Cache::forever($key, $max);
        if ($last === null || $max <= $last) {
            return;
        }
        $count = DB::table('failed_jobs')->where('id', '>', $last)
            ->where('payload', 'not like', '%TranslateLocale%')->where('payload', 'not like', '%TranslateModel%')->count();
        if ($count) {
            Notify::admins('system', __(':count arka plan işi başarısız oldu', ['count' => $count]), __('Yönetim › Arka plan işleri sayfasından tekrar dene ya da sil.'), route('queue.index'));
        }
    }
}
