<?php

namespace App\Console\Commands;

use App\Services\ImageUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Media diskinde hiçbir kayda bağlı olmayan dosyaları çöp kutusuna taşır (30 gün geri alınabilir, App\Support\MediaTrash): kaydedilmeden bırakılan yüklemeler,
 * yazıdan çıkarılan görseller vb. Son 24 saatte yüklenenlere dokunmaz (form hâlâ açık olabilir).
 *
 * "Kullanılıyor mu" sorusu tek tek tablo listesiyle değil, veritabanındaki TÜM metin sütunları
 * taranarak cevaplanır. Böylece yeni bir resim alanı (banner, rehber kapağı, ikon…) eklendiğinde
 * buraya eklemeyi unutmak, o resimlerin gece silinmesine yol açmaz.
 */
class PruneMedia extends Command
{
    protected $signature = 'media:prune {--dry-run : Silmeden sadece listele}';

    protected $description = 'Hiçbir kayda bağlı olmayan resim dosyalarını siler';

    /** Dosya yolu referansı tutmayan / geçici tablolar */
    private const SKIP_TABLES = [
        'cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'migrations',
        'password_reset_tokens', 'visits', 'affiliate_clicks',
        'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients', 'oauth_device_codes', 'oauth_refresh_tokens',
    ];

    private const TEXT_TYPES = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'json'];

    /** ImageUploader'ın ürettiği yollar: klasör/yyyy/mm/20 harf[-sm|-md].uzantı */
    private const PATH_RE = '~[a-z_-]+/\d{4}/\d{2}/[a-z0-9]{20}(?:-sm|-md)?\.[a-z0-9]+~';

    public function handle(): int
    {
        $used = collect();
        $database = DB::connection()->getDatabaseName();

        $tables = collect(Schema::getTables())
            ->filter(fn (array $t) => ($t['schema'] ?? $database) === $database)
            ->pluck('name')
            ->reject(fn (string $name) => in_array($name, self::SKIP_TABLES, true));

        foreach ($tables as $table) {
            $columns = collect(Schema::getColumns($table))
                ->filter(fn (array $c) => in_array(strtolower($c['type_name']), self::TEXT_TYPES, true))
                ->pluck('name');
            if ($columns->isEmpty()) {
                continue;
            }

            $query = DB::table($table)->select($columns->all());
            // Sadece resim yolu geçebilecek satırlar
            $query->where(function ($q) use ($columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%/20__%');
                }
            });

            foreach ($query->cursor() as $row) {
                foreach ((array) $row as $value) {
                    if (!is_string($value) || $value === '') {
                        continue;
                    }
                    // JSON (çeviriler) içinde "/" → "\/" olarak saklanır
                    preg_match_all(self::PATH_RE, str_replace('\/', '/', $value), $m);
                    foreach ($m[0] as $path) {
                        $used->push($path);
                        // Ana dosya kullanılıyorsa küçük/orta kopyaları da kalsın
                        $base = preg_replace('/-(sm|md)(\.[a-z0-9]+)$/', '$2', $path);
                        if (str_ends_with($base, '.webp')) {
                            $used->push(...ImageUploader::files($base));
                        }
                    }
                }
            }
        }

        $used = $used->flip();
        $disk = Storage::disk('media');
        // Çöp kutusundakiler taranmaz (onları aşağıda MediaTrash::purge 30 gün sonra siler)
        $files = collect($disk->allFiles())->reject(fn (string $file) => str_starts_with($file, \App\Support\MediaTrash::DIR . '/'));

        // Güvenlik: diskte dosya varken veritabanında hiç referans bulunamadıysa bir terslik vardır; silme.
        if ($used->isEmpty() && $files->isNotEmpty()) {
            $this->error('Veritabanında hiç resim referansı bulunamadı; güvenlik için hiçbir şey silinmedi.');

            return self::FAILURE;
        }

        $cutoff = now()->subDay()->getTimestamp();
        $orphans = $files
            ->filter(fn (string $file) => str_ends_with($file, '.webp') && !$used->has($file) && $disk->lastModified($file) < $cutoff)
            ->values();

        foreach ($orphans as $file) {
            $this->line(($this->option('dry-run') ? 'Çöp kutusuna gidecek: ' : 'Çöp kutusuna taşındı: ') . $file);
        }
        if (!$this->option('dry-run')) {
            // Hemen silinmez: 30 gün çöp kutusunda durur, panelden geri alınabilir
            \App\Support\MediaTrash::trash($orphans->all());
            $purged = \App\Support\MediaTrash::purge();
            $purged && $this->info($purged . ' dosya 30 günü doldurduğu için çöp kutusundan kalıcı silindi.');
        }

        $this->info($orphans->count() . ' sahipsiz dosya' . ($this->option('dry-run') ? ' bulundu.' : ' çöp kutusuna taşındı.'));

        return self::SUCCESS;
    }
}
