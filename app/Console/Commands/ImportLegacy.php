<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Blog;
use App\Models\BlogSubscriber;
use App\Models\City;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Redirect;
use App\Models\Review;
use App\Services\ImageUploader;
use App\Support\SitePath;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Eski sitenin verisini yeni yapıya aktarır: aktiviteler, SSS, blog yazıları, yorumlar, blog aboneleri
 * ve görseller. Eski sitede slug'lar dillere çevrilmişti (/de/activities/almanca-adres…); bunlar tek slug'a yönlendirilir. Eski DB dökümü geçici bir veritabanına yüklenip buradan okunur.
 * Görseller canlı sitedeki /media/{id} adresinden indirilip yeniden boyutlandırılır (eski dosyalar sıkıştırılmamıştı).
 * Tekrar çalıştırmak güvenli: var olan kayıtlar atlanır.
 */
class ImportLegacy extends Command
{
    protected $signature = 'legacy:import {database : Eski veritabanının adı}
        {--host=127.0.0.1} {--username=root} {--password=}
        {--media-url=https://tripspoiler.com/media : Eski görsellerin indirileceği adres}
        {--no-images : Görselleri indirme}';

    protected $description = 'Eski sitenin içeriğini (aktivite, SSS, blog, yorum, abone, görsel) yeni yapıya aktarır';

    private Connection $legacy;

    /** Eski aktivite id → yeni aktivite */
    private Collection $activities;

    /** Eski blog id → yeni blog */
    private Collection $blogs;

    public function __construct(private ImageUploader $uploader)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        Config::set('database.connections.legacy', array_merge(config('database.connections.mysql'), [
            'host' => $this->option('host'),
            'database' => $this->argument('database'),
            'username' => $this->option('username'),
            'password' => $this->option('password'),
        ]));
        $this->legacy = DB::connection('legacy');

        $this->importActivities();
        $this->importFaqs();
        $this->importBlogs();
        $this->importReviews();
        $this->importSubscribers();
        $this->importRedirects();

        return self::SUCCESS;
    }

    private function importActivities(): void
    {
        $cities = $this->legacy->table('cities')->get()->mapWithKeys(fn ($c) => [$c->id => $this->en($c->slug)]);
        $partners = $this->legacy->table('affiliate_partners')->get()->mapWithKeys(fn ($p) => [$p->id => Str::slug((string) $this->en($p->name), '')]);
        $images = $this->images('activity');
        $this->activities = collect();
        $imported = 0;

        foreach ($this->legacy->table('activities')->orderBy('city_id')->orderBy('sort_order')->get() as $old) {
            $slug = Str::slug((string) $this->en($old->slug));

            if ($existing = Activity::where('slug', $slug)->first()) {
                $this->activities[$old->id] = $existing;
                continue;
            }

            $city = City::with('country')->where('slug', $cities[$old->city_id] ?? null)->first();
            if (!$city) {
                $this->warn("Şehir bulunamadı, atlandı: {$slug}");
                continue;
            }

            $activity = Activity::create([
                'city_id' => $city->id,
                'partner_id' => Partner::where('slug', $partners[$old->affiliate_id] ?? null)->value('id'),
                'name' => $this->translations($old->name),
                'slug' => $slug,
                'type' => $old->activity_type ?: 'product',
                'affiliate_url' => $old->affiliate_link,
                'partner_product_id' => $old->source_product_id,
                'duration' => $this->en($old->duration),
                'audio_guide' => (bool) $old->audio_guide,
                'description' => $this->translations($old->description),
                'meta_title' => $this->translations($old->meta_title),
                'meta_description' => $this->translations($old->meta_description),
                'is_popular' => (bool) $old->most_popular,
                'active' => (bool) $old->status,
                'sort_order' => $old->sort_order,
            ]);
            $this->activities[$old->id] = $activity;

            // Aktivitesi olan şehir ve ülkesi sitede görünsün
            $city->update(['active' => true]);
            $city->country->update(['active' => true]);

            foreach ($images->get($old->id, []) as $i => $image) {
                if ($saved = $this->download($image->id, 'activity')) {
                    $activity->images()->create([...$saved, 'sort_order' => $i]);
                }
            }
            $imported++;
        }

        $this->info("{$imported} aktivite aktarıldı.");
    }

    private function importFaqs(): void
    {
        $imported = 0;

        foreach ($this->legacy->table('faqs')->orderBy('source_id')->orderBy('sort_order')->get() as $old) {
            $activityId = null;
            if ($old->source === 'activity-show') {
                $activityId = $this->activities->get($old->source_id)?->id ?? null;
                if (!$activityId) {
                    continue;
                }
            }

            $question = $this->translations($old->question);
            if (blank($question['en'] ?? null) || Faq::where('activity_id', $activityId)->where('question->en', $question['en'])->exists()) {
                continue;
            }

            Faq::create([
                'activity_id' => $activityId,
                'question' => $question,
                'answer' => $this->translations($old->answer) ?: ['en' => ''],
                'sort_order' => $old->sort_order,
                'active' => (bool) $old->status,
            ]);
            $imported++;
        }

        $this->info("{$imported} soru-cevap aktarıldı.");
    }

    private function importBlogs(): void
    {
        $cities = $this->legacy->table('cities')->get()->mapWithKeys(fn ($c) => [$c->id => $this->en($c->slug)]);
        $covers = $this->images('blog');
        $sectionImages = $this->images('blog_content');
        $this->blogs = collect();
        $imported = 0;

        foreach ($this->legacy->table('blogs')->orderBy('sort_order')->get() as $old) {
            $slug = Str::slug((string) $this->en($old->slug));
            if ($slug === '') {
                continue;
            }
            if ($existing = Blog::where('slug', $slug)->first()) {
                $this->blogs[$old->id] = $existing;
                continue;
            }

            // Eski yazı bölümlerden oluşuyordu: başlık + metin + görseller → tek içerik (her dil ayrı)
            $content = [];
            foreach ($this->legacy->table('blog_contents')->where('blog_id', $old->id)->orderBy('sort_order')->get() as $section) {
                $figures = collect($sectionImages->get($section->id, []))
                    ->map(fn ($image) => $this->download($image->id, 'content'))->filter()
                    ->map(fn ($saved) => '<p><img src="' . ImageUploader::url($saved['path']) . '" alt=""></p>')->implode('');

                foreach ($this->translations($section->content) as $locale => $html) {
                    $title = $this->translations($section->title)[$locale] ?? null;
                    $content[$locale] = trim(($content[$locale] ?? '') . ($title ? "<h2>{$title}</h2>" : '') . $html . $figures);
                }
            }

            $cover = ($image = collect($covers->get($old->id, []))->first()) ? $this->download($image->id, 'cover') : null;

            $blog = Blog::create([
                'city_id' => City::where('slug', $cities[$old->city_id] ?? null)->value('id'),
                'title' => $this->translations($old->title),
                'slug' => $slug,
                'excerpt' => $this->translations($old->excerpt),
                'content' => array_filter($content),
                'tags' => array_filter($this->translations($old->themes)),
                'cover_path' => $cover['path'] ?? null,
                'meta_title' => $this->translations($old->meta_title),
                'meta_description' => $this->translations($old->meta_description),
                'active' => (bool) $old->status,
                'published_at' => $old->created_at,
            ]);

            $this->blogs[$old->id] = $blog;

            $activityIds = $this->legacy->table('activity_blog')->where('blog_id', $old->id)->pluck('activity_id')
                ->map(fn ($id) => $this->activities->get($id)?->id ?? null)->filter()->values();
            $blog->activities()->sync($activityIds->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
            $imported++;
        }

        $this->info("{$imported} blog yazısı aktarıldı.");
    }

    private function importReviews(): void
    {
        $imported = 0;

        foreach ($this->legacy->table('reviews')->orderBy('id')->get() as $old) {
            $activityId = $old->source === 'activity' ? ($this->activities->get($old->source_id)?->id ?? null) : null;
            $name = $this->decrypt($old->name);

            // Aktivitesi olmayan aktivite yorumu = eski deneme kaydı
            if (($old->source === 'activity' && !$activityId) || blank($name) || Review::where('name', $name)->where('comment', $old->comment)->exists()) {
                continue;
            }

            $review = new Review([
                'activity_id' => $activityId,
                'name' => $name,
                'email' => $this->decrypt($old->email),
                'rating' => $old->rating,
                'comment' => (string) $old->comment,
                'approved' => (bool) $old->approved,
            ]);
            $review->created_at = $old->created_at;
            $review->save();
            $imported++;
        }

        $this->info("{$imported} yorum aktarıldı.");
    }

    private function importSubscribers(): void
    {
        $imported = 0;

        foreach ($this->legacy->table('blog_subscribers')->orderBy('id')->get() as $old) {
            $email = $this->decrypt($old->email);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || BlogSubscriber::findByEmail($email)) {
                continue;
            }

            $subscriber = new BlogSubscriber(['email' => $email, 'unsubscribed_at' => $old->status ? null : $old->updated_at]);
            $subscriber->created_at = $old->created_at;
            $subscriber->save();
            $imported++;
        }

        $this->info("{$imported} blog abonesi aktarıldı.");
    }

    /**
     * Eski sitede her dilin kendi slug'ı vardı (ör. /de/activities/gefuhrter-eintritt…, /zh/activities/在导游…).
     * Yeni sitede slug tek: eski adresler 301 ile yenisine gider, Search Console'da 404 kalmaz.
     */
    private function importRedirects(): void
    {
        $count = 0;
        $point = function (string $type, ?string $slugs, ?string $new) use (&$count) {
            if (!$new) {
                return;
            }
            foreach ($this->translations($slugs) as $locale => $old) {
                if (is_string($old) && $old !== $new) {
                    Redirect::point(SitePath::for($type, $locale, $old), SitePath::for($type, $locale, $new));
                    $count++;
                }
            }
        };

        foreach ($this->legacy->table('activities')->get() as $old) {
            $point('activity', $old->slug, $this->activities->get($old->id)?->slug);
        }
        foreach ($this->legacy->table('blogs')->get() as $old) {
            $point('blog', $old->slug, $this->blogs->get($old->id)?->slug);
        }
        foreach ($this->legacy->table('cities')->get() as $old) {
            $point('city', $old->slug, City::where('slug', $this->en($old->slug))->value('slug'));
        }

        $this->info("{$count} eski adres yönlendirmesi hazır.");
    }

    /**
     * Eski "images" tablosundan bir türün görselleri, sahibine göre gruplu ve sıralı.
     */
    private function images(string $source): Collection
    {
        return $this->legacy->table('images')->where('source', $source)->orderBy('sort_order')->orderBy('id')->get()->groupBy('source_id');
    }

    /**
     * Eski görseli canlı siteden indirir ve yeni düzende kaydeder.
     *
     * @param  'activity'|'cover'|'content'  $type
     * @return array{path: string, width?: int, height?: int}|null
     */
    private function download(int $id, string $type): ?array
    {
        if ($this->option('no-images')) {
            return null;
        }

        try {
            $response = Http::timeout(60)->retry(2, 1000, throw: false)->get(rtrim($this->option('media-url'), '/') . '/' . $id);
            if (!$response->successful() || !str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                $this->warn("Görsel indirilemedi (eski id {$id}).");

                return null;
            }

            return match ($type) {
                'activity' => $this->uploader->activity($response->body()),
                'content' => $this->uploader->content($response->body()),
                'cover' => ['path' => $this->uploader->cover($response->body())],
            };
        } catch (Throwable $e) {
            $this->warn("Görsel indirilemedi (eski id {$id}): {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Eski sitede şifreli tutulan alanlar (aynı APP_KEY); şifreli değilse olduğu gibi.
     */
    private function decrypt(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * Eski JSON çeviri alanı → boş olmayan diller.
     */
    private function translations(?string $json): array
    {
        return array_filter((array) json_decode((string) $json, true), fn ($value) => filled($value));
    }

    private function en(?string $value): ?string
    {
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) ? ($decoded['en'] ?? null) : $value;
    }
}
