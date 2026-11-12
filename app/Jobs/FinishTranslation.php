<?php

namespace App\Jobs;

use App\Models\Language;
use App\Support\ActivityLogger;
use App\Support\TranslationJobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Sıradaki çevirinin (TranslateLocale zinciri) son adımı: "Çevrildi" bildirimi + işlem kaydı */
class FinishTranslation implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $locales
     */
    public function __construct(
        public string $key,
        public array $locales,
        public string $logType,
        public int $id,
        public string $title,
    ) {}

    public function handle(): void
    {
        $names = Language::whereIn('code', $this->locales)->pluck('native_name')->implode(', ');
        TranslationJobs::finish($this->key, __('Çevrildi: :languages', ['languages' => $names]));
        ActivityLogger::write('translated', $this->logType, $this->id, $this->title, ['Diller' => [null, $names]]);
    }
}
