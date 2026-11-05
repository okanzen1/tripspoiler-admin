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

    public function handle(ModelTranslator $translator): void
    {
        $translator->translate($this->model, $this->locales);
    }
}
