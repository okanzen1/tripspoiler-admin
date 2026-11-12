<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Yüklenen resimleri boyutlandırıp WebP olarak "media" diskine kaydeder.
 *
 * Yol düzeni: {klasör}/{yıl}/{ay}/{rastgele}.webp — tek klasörde binlerce dosya birikmez.
 * Listelerde kullanılan küçük kopya aynı adın "-sm" ekli halidir ({rastgele}-sm.webp); url($path, 'sm') verir.
 * Kaynak: yüklenen dosya ya da ham resim verisi (ör. eski siteden indirilen).
 */
class ImageUploader
{
    /** Küçük kopyanın en geniş kenarı (liste kartları, önizlemeler) */
    public const SMALL = 640;

    private ImageManager $images;

    public function __construct()
    {
        $this->images = new ImageManager(new Driver());
    }

    /**
     * Logo: oranı korunur, en fazla 400x400; şeffaflık kalır.
     */
    public function logo(UploadedFile|string $file, ?string $replace = null): string
    {
        // SVG logolar olduğu gibi saklanır (vektör; her boyutta net). Betik içerenler reddedilir.
        if ($file instanceof UploadedFile && strtolower($file->getClientOriginalExtension()) === 'svg') {
            $svg = (string) file_get_contents($file->getRealPath());
            if (!str_contains($svg, '<svg') || preg_match('/<script|on\w+\s*=|javascript:|<foreignObject/i', $svg)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['logo' => __('Bu SVG dosyası kullanılamaz.')]);
            }
            $path = 'partners/' . now()->format('Y/m') . '/' . Str::lower(Str::random(20)) . '.svg';
            Storage::disk('media')->put($path, $svg);
            $this->delete($replace);

            return $path;
        }

        return $this->save('partners', $this->images->read($file)->scaleDown(400, 400), 90, $replace)['path'];
    }

    /**
     * Profil resmi: ortadan kare kırpılır, 256x256.
     */
    public function avatar(UploadedFile|string $file, ?string $replace = null): string
    {
        return $this->save('avatars', $this->images->read($file)->cover(256, 256), 85, $replace)['path'];
    }

    /**
     * Aktivite görseli: en fazla 1600x1200 + küçük kopya. Ölçüler sitede yer ayırmak (zıplama olmasın) için döner.
     *
     * @return array{path: string, width: int, height: int}
     */
    public function activity(UploadedFile|string $file): array
    {
        return $this->save('activities', $this->images->read($file)->scaleDown(1600, 1200), 82, small: true);
    }

    /**
     * Blog kapak görseli: en fazla 1600x1200 + küçük kopya.
     */
    public function cover(UploadedFile|string $file, ?string $replace = null): string
    {
        return $this->save('blogs', $this->images->read($file)->scaleDown(1600, 1200), 82, $replace, small: true)['path'];
    }

    /**
     * Yazı içi görsel (editörden eklenen): en fazla 1200 genişlik.
     *
     * @return array{path: string, width: int, height: int}
     */
    public function content(UploadedFile|string $file): array
    {
        return $this->save('content', $this->images->read($file)->scaleDown(1200, 1600), 82);
    }

    /**
     * Özellik kartı ikonu. SVG olduğu gibi (güvenlik için betik içerenler reddedilir), PNG/WebP en fazla 256 px WebP.
     * Sitede ikonun sadece şekli kullanılır (CSS mask), renk otomatik beyaz olur.
     */
    public function icon(UploadedFile $file, ?string $replace = null): string
    {
        if (in_array($file->getMimeType(), ['image/svg+xml', 'image/svg'], true) || strtolower($file->getClientOriginalExtension()) === 'svg') {
            $svg = (string) file_get_contents($file->getRealPath());
            if (!str_contains($svg, '<svg') || preg_match('/<script|on\w+\s*=|javascript:|<foreignObject/i', $svg)) {
                throw new \InvalidArgumentException(__('Bu SVG dosyası kullanılamaz.'));
            }
            $path = 'icons/' . now()->format('Y/m') . '/' . Str::lower(Str::random(20)) . '.svg';
            Storage::disk('media')->put($path, $svg);
            $this->delete($replace);

            return $path;
        }

        return $this->save('icons', $this->images->read($file)->scaleDown(256, 256), 90, $replace)['path'];
    }

    /**
     * Anasayfa bölüm resmi (ör. Şehirler çağrısı): en fazla 2000x1400 + küçük kopya.
     */
    public function section(UploadedFile|string $file, ?string $replace = null): string
    {
        return $this->save('sections', $this->images->read($file)->scaleDown(2000, 1400), 80, $replace, small: true)['path'];
    }

    /**
     * Anasayfa hero görseli (masaüstü): en fazla 2560x1440 + 1920 ("-md") ve 1280 ("-sm") kopyaları.
     * Site, ziyaretçinin ekranına uygun olanı srcset ile seçer.
     */
    public function hero(UploadedFile|string $file, ?string $replace = null): string
    {
        $image = $this->images->read($file)->scaleDown(2560, 1440);
        $saved = $this->save('hero', $image, 80, $replace, small: false);

        $disk = Storage::disk('media');
        $disk->put(self::variant($saved['path'], 'md'), (string) (clone $image)->scaleDown(1920, 1920)->toWebp(80));
        $disk->put(self::variant($saved['path'], 'sm'), (string) (clone $image)->scaleDown(1280, 1280)->toWebp(78));

        return $saved['path'];
    }

    /**
     * Anasayfa hero görseli (telefon, dikey): en fazla 1080x1920.
     */
    public function heroMobile(UploadedFile|string $file, ?string $replace = null): string
    {
        return $this->save('hero', $this->images->read($file)->scaleDown(1080, 1920), 80, $replace)['path'];
    }

    /**
     * Dosyayı (ve küçük/orta kopyalarını) çöp kutusuna taşır: 30 gün geri alınabilir (App\Support\MediaTrash).
     * Veritabanı işlemi içindeyse işlem başarıyla bitince taşır.
     */
    public function delete(?string $path): void
    {
        if ($path) {
            DB::afterCommit(fn () => \App\Support\MediaTrash::trash(self::files($path)));
        }
    }

    /**
     * @param  'sm'|'md'|null  $size  küçük kopya "sm", orta kopya "md" (sadece hero)
     */
    public static function url(?string $path, ?string $size = null): ?string
    {
        return $path ? Storage::disk('media')->url($size ? self::variant($path, $size) : $path) : null;
    }

    /**
     * Media diskinin kök adresi (sonunda / ile); içerikteki görsellerin bize ait olduğunu anlamak için.
     */
    public static function baseUrl(): string
    {
        return rtrim(Storage::disk('media')->url(''), '/') . '/';
    }

    /**
     * Bir resim yolunun diskteki tüm dosyaları (asıl + küçük ve orta kopya; olmayanlar yok sayılır).
     *
     * @return list<string>
     */
    public static function files(string $path): array
    {
        return [$path, self::variant($path, 'sm'), self::variant($path, 'md')];
    }

    private static function variant(string $path, string $size): string
    {
        return preg_replace('/\.webp$/', "-{$size}.webp", $path);
    }

    /**
     * @return array{path: string, width: int, height: int}
     */
    private function save(string $folder, ImageInterface $image, int $quality, ?string $replace = null, bool $small = false): array
    {
        $path = $folder . '/' . now()->format('Y/m') . '/' . Str::lower(Str::random(20)) . '.webp';
        $disk = Storage::disk('media');

        $disk->put($path, (string) $image->toWebp($quality));
        if ($small) {
            $disk->put(self::variant($path, 'sm'), (string) (clone $image)->scaleDown(self::SMALL, self::SMALL)->toWebp(78));
        }
        $this->delete($replace);

        return ['path' => $path, 'width' => $image->width(), 'height' => $image->height()];
    }
}
