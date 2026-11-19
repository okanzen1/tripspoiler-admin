<?php

namespace App\Jobs;

use App\Services\Translation\ModelTranslator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Toplu çeviride bir kaydın verilen (yoksa eksik/eskimiş) dillerini çevirir (sırada; php artisan queue:work).
 * Google çok sık istekte geçici engel koyabilir: hata olursa birkaç dakika sonra tekrar denenir.
 */
class TranslateModel implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 900;

    /** @var list<int> */
    public array $backoff = [120, 300, 600, 1200];

    /**
     * @param  list<string>|null  $locales
     */
    public function __construct(public Model $model, public ?array $locales = null) {}

    public function uniqueId(): string
    {
        return $this->model->getMorphClass() . ':' . $this->model->getKey() . ':' . implode(',', $this->locales ?? ['*']);
    }

    /** Bölünmüş tek dil işi, kaynak iş "yalnızca eksikleri" (dil verilmeden) sıraya alındıysa: güncel kaydı zorla yeniden çevirme */
    public bool $onlyPending = false;

    public function handle(ModelTranslator $translator): void
    {
        // Sıraya girdikten sonra başka bir iş (ör. sorunun ait olduğu blog yazısı) bu dilleri zaten çevirmiş olabilir:
        // çalıştığı anda hâlâ eksik/eskimiş olan dillerle sınırla, güncel olanı baştan çevirme
        $pending = $translator->fullPending($this->model);
        $locales = $this->locales === null ? $pending : array_values(array_intersect($this->locales, $pending));
        if ($locales === []) {
            return;
        }
        $onlyPending = $this->locales === null || $this->onlyPending;

        // Birden çok dil: her dil ayrı, kısa bir iş olsun. Tek işte 11 dil dakikalar sürüyor; paylaşımlı sunucu
        // işlemi yarıda kesince iş takılı kalıyor ve sıra donuyordu. Kesilen tek dil işi sadece o dili tekrar dener.
        if (count($locales) > 1) {
            foreach ($locales as $locale) {
                $job = new self($this->model, [$locale]);
                $job->onlyPending = $onlyPending;
                dispatch($job);
            }

            return;
        }

        $translator->translate($this->model, $locales, null, !$onlyPending);
        \App\Support\RecentTranslations::push($this->model, $locales);
    }
}
