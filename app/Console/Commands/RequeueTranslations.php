<?php

namespace App\Console\Commands;

use App\Jobs\TranslateModel;
use App\Services\Translation\ModelTranslator;
use App\Support\BulkTranslation;
use App\Support\Queues;
use App\Support\TranslationTodo;
use Illuminate\Bus\UniqueLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Toplu çeviri sırasını temizler ve baştan kurar: çevirisi eksik her kayıt sıraya BİR kez girer.
 * "Tümünü sırayla çevir"e eskiden defalarca basılınca aynı kayıt onlarca kez sıradaydı (306 kayıt için 29.000+ iş).
 * Şu an çalışan iş silinmez. Kaydın "zaten sırada" kilidi de açılır ki yeniden sıraya girebilsin.
 */
class RequeueTranslations extends Command
{
    protected $signature = 'translations:requeue';

    protected $description = 'Toplu çeviri sırasını temizler, çevirisi eksik her kaydı bir kez sıraya alır';

    public function handle(): int
    {
        $deleted = DB::table('jobs')->whereIn('queue', [Queues::BULK, Queues::BULK_NEXT])->whereNull('reserved_at')->delete();
        $this->line("Silinen tekrarlı iş: {$deleted}");

        TranslationTodo::refresh(force: true);
        $keys = BulkTranslation::pendingKeys();

        // eski işlerin "bu kayıt zaten sırada" kilitleri: yoksa yeni iş sessizce atlanırdı
        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key, 2);
            $class = ModelTranslator::TYPES[$type] ?? null;
            if ($class) {
                $model = (new $class)->forceFill(['id' => (int) $id]);
                Cache::lock(UniqueLock::getKey(new TranslateModel($model)))->forceRelease();
            }
        }

        $count = BulkTranslation::queue($keys, null, null);
        $this->info("Sıraya alınan kayıt: {$count}");

        return self::SUCCESS;
    }
}
