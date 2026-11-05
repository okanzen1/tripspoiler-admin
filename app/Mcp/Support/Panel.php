<?php

namespace App\Mcp\Support;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\City;
use App\Models\Partner;
use App\Services\ImageUploader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Claude araçlarının ortak işleri: adla kayıt bulma, görsel indirme, panel linkleri.
 */
class Panel
{
    /**
     * Şehri id ya da adla bulur ("Istanbul", "İzmir, Türkiye"). Aynı adda birden çok şehir varsa nüfusu en büyük olan.
     */
    public static function city(string|int $city): City
    {
        if (is_numeric($city)) {
            return City::with('country')->findOrFail((int) $city);
        }

        [$name, $country] = array_map('trim', explode(',', $city . ','));
        $query = City::with('country')
            ->where(fn ($q) => $q->where('name', $name)->orWhere('slug', Str::slug($name)))
            ->when($country !== '', fn ($q) => $q->whereHas('country', fn ($q) => $q->where('name', $country)->orWhere('code', strtoupper($country))));

        return $query->orderByDesc('population')->first()
            ?? throw new RuntimeException("City \"{$city}\" not found. Use list-cities to search.");
    }

    public static function partner(string|int|null $partner): ?Partner
    {
        if (blank($partner)) {
            return null;
        }

        return (is_numeric($partner) ? Partner::find((int) $partner) : Partner::where('name', $partner)->orWhere('slug', Str::slug($partner, ''))->first())
            ?? throw new RuntimeException("Partner \"{$partner}\" not found. Existing partners: " . Partner::pluck('name')->implode(', '));
    }

    public static function activity(string|int $activity): Activity
    {
        return (is_numeric($activity) ? Activity::find((int) $activity) : Activity::where('slug', $activity)->orWhere('name->en', $activity)->first())
            ?? throw new RuntimeException("Activity \"{$activity}\" not found. Use search-activities.");
    }

    public static function blog(string|int $blog): Blog
    {
        return (is_numeric($blog) ? Blog::find((int) $blog) : Blog::where('slug', $blog)->orWhere('title->en', $blog)->first())
            ?? throw new RuntimeException("Blog post \"{$blog}\" not found.");
    }

    /**
     * Benzersiz slug: "hagia-sophia-tour", alınmışsa "hagia-sophia-tour-2".
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    public static function slug(string $model, string $text, ?int $ignoreId = null): string
    {
        $base = Str::slug($text) ?: Str::lower(Str::random(8));
        $slug = $base;
        for ($i = 2; $model::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Adresteki görseli indirir; boyutlandırıp kaydetmeyi verilen fonksiyona bırakır.
     *
     * @return array{saved: list<mixed>, failed: list<string>}
     */
    public static function downloadImages(array $urls, callable $save): array
    {
        $saved = [];
        $failed = [];
        foreach (array_slice(array_unique(array_filter($urls)), 0, 15) as $url) {
            try {
                $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0 TripSpoiler'])->get($url);
                if (!$response->successful() || !str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                    throw new RuntimeException('not an image');
                }
                $saved[] = $save($response->body());
            } catch (Throwable) {
                $failed[] = $url;
            }
        }

        return ['saved' => $saved, 'failed' => $failed];
    }

    public static function editUrl(string $route, mixed $model): string
    {
        return route($route, $model);
    }
}
