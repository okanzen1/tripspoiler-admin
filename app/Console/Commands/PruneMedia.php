<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\ActivityImage;
use App\Models\Blog;
use App\Models\Partner;
use App\Models\User;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Media diskinde hiçbir kayda bağlı olmayan dosyaları siler: kaydedilmeden bırakılan yüklemeler,
 * yazıdan çıkarılan görseller vb. Son 24 saatte yüklenenlere dokunmaz (form hâlâ açık olabilir).
 */
class PruneMedia extends Command
{
    protected $signature = 'media:prune {--dry-run : Silmeden sadece listele}';

    protected $description = 'Hiçbir kayda bağlı olmayan resim dosyalarını siler';

    public function handle(): int
    {
        $used = collect()
            ->merge(ActivityImage::pluck('path'))
            ->merge(Blog::whereNotNull('cover_path')->pluck('cover_path'))
            ->merge(Partner::whereNotNull('logo_path')->pluck('logo_path'))
            ->merge(User::whereNotNull('avatar_path')->pluck('avatar_path'))
            ->flatMap(fn (string $path) => ImageUploader::files($path));

        // Yazı içi görseller: tüm dillerdeki içerikler
        Blog::select('id', 'content')->lazyById()->each(function (Blog $blog) use ($used) {
            foreach ($blog->getTranslations('content') as $html) {
                $used->push(...HtmlSanitizer::imagePaths($html));
            }
        });
        Activity::select('id', 'description')->lazyById()->each(function (Activity $activity) use ($used) {
            foreach ($activity->getTranslations('description') as $html) {
                $used->push(...HtmlSanitizer::imagePaths($html));
            }
        });

        $used = $used->flip();
        $disk = Storage::disk('media');
        $cutoff = now()->subDay()->getTimestamp();
        $orphans = collect($disk->allFiles())
            ->filter(fn (string $file) => str_ends_with($file, '.webp') && !$used->has($file) && $disk->lastModified($file) < $cutoff)
            ->values();

        foreach ($orphans as $file) {
            $this->line(($this->option('dry-run') ? 'Silinecek: ' : 'Silindi: ') . $file);
        }
        if (!$this->option('dry-run')) {
            $disk->delete($orphans->all());
        }

        $this->info($orphans->count() . ' sahipsiz dosya' . ($this->option('dry-run') ? ' bulundu.' : ' silindi.'));

        return self::SUCCESS;
    }
}
