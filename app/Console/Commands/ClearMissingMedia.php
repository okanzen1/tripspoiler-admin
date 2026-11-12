<?php

namespace App\Console\Commands;

use App\Services\ImageUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Dosyası diskte olmayan resim kayıtlarını boşaltır. Böylece site "resim var" sanıp boş kutu göstermez,
 * varsayılana geçer (ör. telefon banner'ı yoksa masaüstü banner'ı, banner yoksa anasayfa resmi).
 * Gece temizliğinden (media:prune) sonra ve ilk kurulumda migration ile çalışır.
 */
class ClearMissingMedia extends Command
{
    protected $signature = 'media:clear-missing {--dry-run : Değiştirmeden sadece listele}';

    protected $description = 'Dosyası olmayan resim kayıtlarını boşaltır (site varsayılan resme geçer)';

    /** tablo => boşaltılabilir resim sütunları */
    private const COLUMNS = [
        'page_banners' => ['image', 'image_mobile'],
        'blogs' => ['cover_path'],
        'partners' => ['logo_path'],
        'users' => ['avatar_path'],
        'city_guide_sections' => ['image_path'],
    ];

    /** Resim tutan ayarlar */
    private const SETTINGS = ['hero_image', 'hero_image_mobile', 'city_entry_image', 'intro_cities_image', 'intro_experiences_image'];

    public function handle(): int
    {
        $disk = Storage::disk('media');
        // Güvenlik: resim klasörü boş ya da erişilemiyorsa (yanlış ayar, taşıma…) hiçbir kayda dokunma
        if (!collect($disk->directories())->contains(fn ($dir) => $dir !== \App\Support\MediaTrash::DIR && count($disk->files($dir, true)) > 0)) {
            $this->error('Resim klasöründe dosya bulunamadı; güvenlik için hiçbir kayıt değiştirilmedi.');

            return self::FAILURE;
        }
        $missing = fn (?string $path) => $path && !$disk->exists($path);
        $fixed = 0;

        foreach (self::COLUMNS as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }
                foreach (DB::table($table)->whereNotNull($column)->get(['id', $column]) as $row) {
                    if ($missing($row->{$column})) {
                        $this->line("{$table}.{$column} #{$row->id}: {$row->{$column}}");
                        $this->option('dry-run') || DB::table($table)->where('id', $row->id)->update([$column => null]);
                        $fixed++;
                    }
                }
            }
        }

        foreach (DB::table('settings')->whereIn('key', self::SETTINGS)->whereNotNull('value')->get(['key', 'value']) as $setting) {
            if ($missing($setting->value)) {
                $this->line("settings.{$setting->key}: {$setting->value}");
                $this->option('dry-run') || DB::table('settings')->where('key', $setting->key)->update(['value' => null]);
                $fixed++;
            }
        }

        $this->info($fixed . ' kayıt' . ($this->option('dry-run') ? ' bulundu.' : ' boşaltıldı.'));

        return self::SUCCESS;
    }
}
