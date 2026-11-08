<?php

namespace App\Http\Controllers;

use App\Support\ActivityLogger;
use App\Support\TranslationJobs;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Yönetim › Arka plan işleri: zamanlayıcının (cron) durumu, süren çeviriler, sırada bekleyen ve başarısız işler.
 * Başarısız iş tekrar denenebilir ya da silinebilir; sıra "şimdi işle" ile beklemeden işlenebilir.
 */
class QueueController extends Controller
{
    public function index()
    {
        $lastRun = Cache::get('scheduler:last_run');

        return view('admin.queue.index', [
            'lastRun' => $lastRun ? now()->setTimestamp($lastRun) : null,
            'schedulerOk' => $lastRun && $lastRun >= now()->subMinutes(3)->timestamp,
            'translations' => TranslationJobs::history(),
            'pending' => DB::table('jobs')->orderBy('id')->limit(200)->get()->map(fn ($job) => [
                'id' => $job->id,
                'attempts' => $job->attempts,
                'running' => $job->reserved_at !== null,
                'created_at' => now()->setTimestamp($job->created_at),
                'available_at' => now()->setTimestamp($job->available_at),
                ...$this->describe($job->payload),
            ]),
            'pendingTotal' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->latest('id')->limit(200)->get()->map(fn ($job) => [
                'uuid' => $job->uuid,
                'failed_at' => \Carbon\Carbon::parse($job->failed_at),
                'error' => $this->error($job->exception),
                'error_full' => Str::limit($job->exception, 4000),
                ...$this->describe($job->payload),
            ]),
            'failedTotal' => DB::table('failed_jobs')->count(),
        ]);
    }

    /** Sıradaki işleri beklemeden işle (cevap gönderildikten sonra) */
    public function run()
    {
        defer(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 120, '--tries' => 3]));

        return back()->with('success', __('Sıradaki işler işleniyor; bu sayfa birkaç saniyede bir kendini yeniler.'));
    }

    public function retry(string $uuid)
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        defer(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 120, '--tries' => 3]));

        return back()->with('success', __('İş tekrar sıraya alındı ve işleniyor.'));
    }

    public function retryAll()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        defer(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 120, '--tries' => 3]));

        return back()->with('success', __('Başarısız işlerin hepsi tekrar sıraya alındı ve işleniyor.'));
    }

    public function forget(string $uuid)
    {
        Artisan::call('queue:forget', ['id' => $uuid]);

        return back()->with('success', __('Başarısız iş silindi.'));
    }

    public function flush()
    {
        Artisan::call('queue:flush');

        return back()->with('success', __('Başarısız işlerin hepsi silindi.'));
    }

    public function destroy(int $id)
    {
        DB::table('jobs')->where('id', $id)->delete();

        return back()->with('success', __('İş sıradan çıkarıldı.'));
    }

    /**
     * İşin okunur hali: ne işi (ör. "Çeviri") ve hangi kayıt için ("Blog yazısı · Istanbul guide").
     *
     * @return array{name: string, subject: ?string, detail: ?string}
     */
    private function describe(string $payload): array
    {
        $data = json_decode($payload, true) ?? [];
        $class = $data['displayName'] ?? $data['data']['commandName'] ?? '?';
        $command = (string) ($data['data']['command'] ?? '');

        $subject = null;
        if (preg_match('/s:\d+:"(App\\\\Models\\\\\w+)";s:2:"id";i:(\d+)/', $command, $m)) {
            $model = $m[1]::find((int) $m[2]);
            $type = Str::snake(class_basename($m[1]));
            $subject = (ActivityLogger::TYPES[$type][0] ?? class_basename($m[1])) . ' · ' . ($model ? ActivityLogger::label($model) : '#' . $m[2] . ' (' . __('silinmiş') . ')');
        }
        $detail = preg_match('/s:7:"locales";a:\d+:\{(.*?)\}/', $command, $l) && preg_match_all('/s:\d+:"([a-z-]+)"/', $l[1], $codes)
            ? strtoupper(implode(', ', $codes[1]))
            : null;

        return [
            'name' => match (class_basename($class)) {
                'TranslateModel' => __('Çeviri'),
                'SendQueuedMailable', 'SendQueuedNotifications' => __('E-posta gönderimi'),
                default => Str::headline(class_basename($class)),
            },
            'subject' => $subject,
            'detail' => $detail,
        ];
    }

    /** Hatanın ilk satırı, anlaşılır kısaltmayla */
    private function error(string $exception): string
    {
        $first = trim(strtok($exception, "\n"));
        $first = preg_replace('/^[\w\\\\]+(Exception|Error):\s*/', '', $first);
        $first = preg_replace('/ in \/.*$/', '', $first);

        return Str::limit($first, 300);
    }
}
