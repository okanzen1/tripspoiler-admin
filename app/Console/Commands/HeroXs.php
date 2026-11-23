<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\ImageUploader;
use Illuminate\Console\Command;

/**
 * Şu anki anasayfa hero görselinin telefon kopyasını ("-xs") üretir (yeni yüklenen hero'da kendiliğinden üretilir).
 * Bir kez çalıştırmak yeter; site kopyayı Setting hero_image_xs eşleşince kullanır.
 */
class HeroXs extends Command
{
    protected $signature = 'media:hero-xs';

    protected $description = 'Anasayfa hero görselinin telefon kopyasını üretir';

    public function handle(): int
    {
        $path = Setting::get('hero_image');
        if (!$path) {
            $this->warn('Hero görseli yüklenmemiş.');

            return self::SUCCESS;
        }
        ImageUploader::heroXs($path);
        Setting::put('hero_image_xs', $path);
        $this->info('Telefon kopyası hazır: ' . ImageUploader::url($path, 'xs'));

        return self::SUCCESS;
    }
}
