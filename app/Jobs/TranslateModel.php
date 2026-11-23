<?php

namespace App\Jobs;

use App\Services\Translation\ModelTranslator;
use App\Support\BulkTranslation;
use App\Support\Queues;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Toplu çeviride bir kaydın verilen (yoksa eksik/eskimiş) dillerini çevirir.
 *
 * Kayıt kayıt ilerler: bir kaydın dilleri sırayla bu işte çevrilir. İş ~40 sn'yi geçince kalan diller "devam" sırasına
 * (Queues::BULK_NEXT) girer; o sıra toplu sıradan önce işlendiği için sıradaki kayda geçmeden bu kayıt biter.
 * (Eskiden her dil ayrı iş olarak sıranın sonuna ekleniyordu: önce bütün kayıtların fr'si, sonra it'si… gidiyordu.)
 * Google çok sık istekte geçici engel koyabilir: hata olursa birkaç dakika sonra tekrar denenir.
 */
class TranslateModel implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 600;

    /** Aynı kayıt sırada iki kez durmasın; sunucu işi yarıda öldürürse kilit en geç 1 saatte kalkar */
    public int $uniqueFor = 3600;

    /** @var list<int> */
    public array $backoff = [120, 300, 600, 1200];

    /** Bir işin dil çevirmeye başlayabileceği süre (sn); aşılınca kalan diller devam işine kalır */
    private const BUDGET = 40;

    /** Bölünmüş devam işi, kaynak iş "yalnızca eksikleri" (dil verilmeden) sıraya alındıysa: güncel dili zorla yeniden çevirme */
    public bool $onlyPending = false;

    /** Verilen diller güncel olsa da İngilizceden baştan çevrilir ("yeniden çevir") */
    public bool $force = false;

    /**
     * @param  list<string>|null  $locales
     */
    public function __construct(public Model $model, public ?array $locales = null)
    {
        $this->onQueue(Queues::BULK);
    }

    public function uniqueId(): string
    {
        return $this->model->getMorphClass() . ':' . $this->model->getKey() . ':' . implode(',', $this->locales ?? ['*']);
    }

    public function handle(ModelTranslator $translator): void
    {
        $started = microtime(true);
        // Sıraya girdikten sonra başka bir iş (ör. sorunun ait olduğu blog yazısı) bu dilleri zaten çevirmiş olabilir:
        // çalıştığı anda hâlâ eksik/eskimiş olan dillerle sınırla, güncel olanı baştan çevirme
        $pending = $translator->fullPending($this->model);
        $locales = match (true) {
            $this->locales === null => $pending,
            $this->force => $this->locales,
            default => array_values(array_intersect($this->locales, $pending)),
        };
        $onlyPending = $this->locales === null || $this->onlyPending;

        foreach ($locales as $i => $locale) {
            if ($i > 0 && microtime(true) - $started > self::BUDGET) {
                $this->continueWith(array_slice($locales, $i), $onlyPending);

                return;
            }
            $translator->translate($this->model, [$locale], null, !$onlyPending);
            \App\Support\RecentTranslations::push($this->model, [$locale]);
        }

        if ($this->isBulk()) {
            BulkTranslation::recordDone();
        }
    }

    public function failed(?Throwable $e): void
    {
        if ($this->isBulk()) {
            BulkTranslation::recordFailed();
        }
    }

    private function isBulk(): bool
    {
        return in_array($this->queue, [Queues::BULK, Queues::BULK_NEXT], true);
    }

    /** @param  list<string>  $locales */
    private function continueWith(array $locales, bool $onlyPending): void
    {
        $job = new self($this->model, $locales);
        $job->onlyPending = $onlyPending;
        $job->force = $this->force;
        // toplu çeviride devam sırası (kayıt bitmeden sıradakine geçilmez); diğerlerinde aynı sıra
        $job->onQueue($this->isBulk() ? Queues::BULK_NEXT : ($this->queue ?? Queues::DEFAULT));
        dispatch($job);
    }
}
