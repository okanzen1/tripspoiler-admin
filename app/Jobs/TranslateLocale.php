<?php

namespace App\Jobs;

use App\Services\Translation\ModelTranslator;
use App\Support\TranslationJobs;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Paneldeki "Çevir": bir kaydın (ve SSS'lerinin) TEK dildeki çevirisi. Diller sırayla, birer iş olarak işlenir
 * (Bus::chain; cron her dakika php artisan queue:work çalıştırır). Böylece uzun çeviri sunucunun süre sınırına
 * takılmaz; yarıda kesilirse ya da Google geçici engel koyarsa iş biraz bekleyip kaldığı yerden devam eder
 * (çevrilmiş SSS'ler / diller tekrar çevrilmez). İlerleme panelin sağ üstünde (App\Support\TranslationJobs).
 */
class TranslateLocale implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $timeout = 600;

    /** @var list<int> Google engeli için bekleme (saniye) */
    public array $backoff = [60, 180, 300];

    public function __construct(
        public string $type,
        public int $id,
        public string $locale,
        public string $key,
        public bool $force,
    ) {}

    public function handle(ModelTranslator $translator): void
    {
        $model = (ModelTranslator::TYPES[$this->type] ?? null)::find($this->id);
        if (!$model) {
            return; // kayıt silinmiş
        }
        TranslationJobs::touch($this->key);
        try {
            // tekrar denemede bu dil daha önce bitmişse yeniden çevrilmez
            $force = $this->force && $this->attempts() === 1;
            $translator->heartbeat(fn () => TranslationJobs::touch($this->key))
                ->translate($model, [$this->locale], fn () => TranslationJobs::progress($this->key, $this->locale), $force);
        } catch (Throwable $e) {
            if ($this->attempts() < $this->tries) {
                $wait = $this->backoff[$this->attempts() - 1] ?? 300;
                TranslationJobs::wait($this->key, $wait + 120, __('Google şu an meşgul; :minutes dk sonra kaldığı yerden devam edecek.', ['minutes' => (int) ceil($wait / 60)]));
            }
            throw $e;
        } finally {
            $translator->heartbeat(null);
        }
    }

    public function failed(?Throwable $e): void
    {
        TranslationJobs::fail($this->key, __('Çeviri yapılamadı: :error', ['error' => $e?->getMessage() ?? '']));
    }
}
