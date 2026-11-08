<?php

namespace App\Mcp\Support;

use App\Models;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Claude'un genel araçlarının (list/get/save/delete-record) yönettiği içerik türleri: panelde düzenlenen her şey.
 *
 * Tür → model, başlık alanı, aranacak alanlar, resim alanları (resim adresi verilir, indirilip panel gibi küçültülür),
 * HTML alanları (temizlenir), salt okunur ve gizli alanlar, paneldeki düzenleme sayfası.
 * Çok dilli alanlar: düz metin = İngilizce; {"tr": "...", "de": "..."} ile belirli dillere elle çeviri yazılır.
 */
class ContentTypes
{
    public const TYPES = [
        'activity' => ['model' => Models\Activity::class, 'title' => 'name', 'search' => ['name', 'slug'], 'html' => ['description'], 'route' => 'activities.edit',
            'about' => 'Bookable activity/ticket/tour/city pass linking to a partner. For a new activity from a product link prefer save-activity (it also handles the photo gallery).'],
        'blog' => ['model' => Models\Blog::class, 'title' => 'title', 'search' => ['title', 'slug'], 'html' => ['content'], 'images' => ['cover_path' => 'cover'], 'route' => 'blogs.edit',
            'about' => 'Blog post / travel guide. content is HTML. Prefer save-blog-post for writing a full post.'],
        'faq' => ['model' => Models\Faq::class, 'title' => 'question', 'search' => ['question', 'answer'], 'route' => 'faqs.edit',
            'about' => 'FAQ. Attach with activity_id or blog_id or city_id, or page_key (e.g. "home"); all empty = general FAQ.'],
        'review' => ['model' => Models\Review::class, 'title' => 'name', 'search' => ['name', 'comment'], 'route' => 'reviews.edit', 'hidden' => ['email'],
            'about' => 'Traveller review (rating 1-5, comment, approved). activity_id optional. Only add reviews the user gives you.'],
        'announcement' => ['model' => Models\Announcement::class, 'title' => 'text', 'search' => ['text'], 'route' => 'announcements.edit',
            'about' => 'Short scrolling text in the bar at the very top of the site. link optional.'],
        'step' => ['model' => Models\Step::class, 'title' => 'title', 'search' => ['title'], 'route' => 'steps.edit',
            'about' => 'Home page "How it works" numbered steps (01, 02, 03): title, text, cta, link, icon.'],
        'feature' => ['model' => Models\Feature::class, 'title' => 'title', 'search' => ['title'], 'route' => 'features.edit',
            'about' => 'Small feature cards sliding on the home page: icon, title, one sentence.'],
        'section_text' => ['model' => Models\SectionText::class, 'title' => 'key', 'search' => ['key', 'title'], 'readonly' => ['key'],
            'about' => 'Texts of a page section, found by key: steps, blog, newsletter, intro, social, reviews, city_entry, partners, hero_activities, hero_cities, hero_blog, hero_reviews, hero_about, hero_privacy, hero_contact, and page content blocks (about_story, privacy_who…). title + title_highlight read as one sentence; in hero titles ":city" is replaced by the selected city.'],
        'hero_text' => ['model' => Models\HeroText::class, 'title' => 'title_1', 'search' => [],
            'about' => 'Home page hero (single record, id 1): eyebrow, title_1..title_3 (lines), title_highlight (red word at the end of the last filled line), subtitle, description. Hero image/focus/overlay are settings (hero_*).'],
        'page' => ['model' => Models\Page::class, 'title' => 'name', 'search' => ['name', 'key'], 'readonly' => ['key', 'name', 'path'], 'route' => 'pages.edit',
            'about' => 'Fixed site pages; edit their SEO meta_title / meta_description.'],
        'page_banner' => ['model' => Models\PageBanner::class, 'title' => 'page_key', 'search' => ['page_key'], 'images' => ['image' => 'hero', 'image_mobile' => 'heroMobile'],
            'about' => 'Banner image of a page (page_key: activities, cities, blog, reviews, about). city_id empty = general image; with city_id = that city\'s own image. focus 0-100 = horizontal crop.'],
        'city' => ['model' => Models\City::class, 'title' => 'name', 'search' => ['name', 'slug'], 'route' => 'cities.edit',
            'about' => 'City. active = shown on the site. Prefer set-city-status to publish/hide.'],
        'country' => ['model' => Models\Country::class, 'title' => 'name', 'search' => ['name', 'code'],
            'about' => 'Country. active = shown on the site.'],
        'city_guide' => ['model' => Models\CityGuide::class, 'title' => 'title', 'search' => ['title'],
            'about' => 'City guide intro of a city (one per city_id): title, description, hero_cards ("sectionId,sectionId").'],
        'city_guide_section' => ['model' => Models\CityGuideSection::class, 'title' => 'title', 'search' => ['title'], 'html' => ['content'], 'images' => ['image_path' => 'section'],
            'about' => 'A topic section in a city guide (city_id + topic_id): title, HTML content, image.'],
        'city_guide_topic' => ['model' => Models\CityGuideTopic::class, 'title' => 'name', 'search' => ['name', 'key'], 'route' => 'city-guide-topics.edit',
            'about' => 'Guide topics shared by all cities (e.g. transport, food, neighbourhoods).'],
        'partner' => ['model' => Models\Partner::class, 'title' => 'name', 'search' => ['name', 'slug', 'website'], 'images' => ['logo_path' => 'logo'], 'route' => 'partners.edit',
            'about' => 'Affiliate partner (booking site). website is used to match outbound clicks.'],
        'redirect' => ['model' => Models\Redirect::class, 'title' => 'from_path', 'search' => ['from_path', 'to_path'],
            'about' => '301 redirect from an old path to a new path (e.g. /en/old-page → /en/activities).'],
        'language' => ['model' => Models\Language::class, 'title' => 'native_name', 'search' => ['name', 'code'], 'readonly' => ['code'],
            'about' => 'Site languages; toggle active.'],
        'subscriber' => ['model' => Models\BlogSubscriber::class, 'title' => 'email', 'search' => [],
            'about' => 'Newsletter subscribers (email is encrypted, cannot be searched).'],
        'mail_template' => ['model' => Models\MailTemplate::class, 'title' => 'title', 'search' => ['title'],
            'about' => 'Saved e-mail reply templates.'],
        'user' => ['model' => Models\User::class, 'title' => 'first_name', 'search' => ['first_name', 'last_name', 'email'], 'hidden' => ['password', 'remember_token'], 'writeOnly' => ['password'], 'images' => ['avatar_path' => 'avatar'], 'route' => 'users.edit',
            'about' => 'Panel users. role: admin|editor. password is write-only (min 8 chars).'],
    ];

    public static function spec(string $type): array
    {
        return self::TYPES[$type] ?? throw new RuntimeException("Unknown type \"{$type}\". Types: " . implode(', ', array_keys(self::TYPES)));
    }

    public static function model(string $type): Model
    {
        return new (self::spec($type)['model']);
    }

    public static function find(string $type, int|string $id): Model
    {
        $spec = self::spec($type);
        $model = self::model($type);
        if (is_numeric($id)) {
            return $model->newQuery()->findOrFail((int) $id);
        }
        // id yerine ad/slug/anahtar da olur
        foreach (array_unique(['slug', 'key', 'code', $spec['title']]) as $field) {
            if (!in_array($field, $model->getFillable(), true)) {
                continue;
            }
            $column = in_array($field, $model->translatable ?? [], true) ? "{$field}->en" : $field;
            if ($found = $model->newQuery()->where($column, $id)->first()) {
                return $found;
            }
        }

        throw new RuntimeException("No {$type} \"{$id}\". Use list-records to find it.");
    }

    /** Alan listesi (Claude'a): ad → tür/not */
    public static function fields(string $type): array
    {
        $spec = self::spec($type);
        $model = self::model($type);
        $out = [];
        foreach ($model->getFillable() as $field) {
            if (in_array($field, $spec['hidden'] ?? [], true) && !in_array($field, $spec['writeOnly'] ?? [], true)) {
                continue;
            }
            $cast = $model->getCasts()[$field] ?? null;
            $out[$field] = match (true) {
                in_array($field, $spec['writeOnly'] ?? [], true) => 'write-only',
                in_array($field, $spec['readonly'] ?? [], true) => 'read-only',
                in_array($field, $model->translatable ?? [], true) => 'translatable text' . (in_array($field, $spec['html'] ?? [], true) ? ' (HTML)' : ''),
                isset(($spec['images'] ?? [])[$field]) => 'image: give an image URL to upload, or null to remove',
                in_array($field, $spec['html'] ?? [], true) => 'HTML',
                $cast === 'boolean' => 'boolean',
                str_ends_with($field, '_id') => 'id (or name for city/partner/activity/blog/country)',
                $cast && is_subclass_of($cast, BackedEnum::class) => 'one of: ' . implode('|', array_map(fn ($c) => $c->value, $cast::cases())),
                in_array($cast, ['datetime', 'date'], true) => 'date (YYYY-MM-DD or YYYY-MM-DD HH:MM)',
                default => 'text/number',
            };
        }

        return $out;
    }

    /** Kaydın Claude'a gösterilen hali: çok dilli alanlar İngilizce + hangi dillerde çevirisi var */
    public static function present(string $type, Model $model, bool $full = false): array
    {
        $spec = self::spec($type);
        $data = ['id' => $model->getKey(), 'title' => self::title($type, $model)];
        if ($full) {
            foreach ($model->getFillable() as $field) {
                if (in_array($field, $spec['hidden'] ?? [], true)) {
                    continue;
                }
                if (in_array($field, $model->translatable ?? [], true)) {
                    $data[$field] = $model->getTranslation($field, 'en', false);
                    $data["{$field}__languages"] = array_keys(array_filter($model->getTranslations($field)));
                } elseif (isset(($spec['images'] ?? [])[$field])) {
                    $data[$field] = ImageUploader::url($model->getAttribute($field));
                } else {
                    $value = $model->getAttribute($field);
                    $data[$field] = $value instanceof BackedEnum ? $value->value : ($value instanceof Carbon ? $value->toDateTimeString() : $value);
                }
            }
        } else {
            foreach (['active', 'approved', 'page_key', 'city_id', 'rating', 'sort_order', 'role', 'published_at'] as $field) {
                if (in_array($field, $model->getFillable(), true)) {
                    $value = $model->getAttribute($field);
                    $data[$field] = $value instanceof BackedEnum ? $value->value : ($value instanceof Carbon ? $value->toDateString() : $value);
                }
            }
        }
        if (($route = $spec['route'] ?? null) && Route::has($route)) {
            $data['edit_url'] = rescue(fn () => route($route, $model), null, false);
        }

        return $data;
    }

    public static function title(string $type, Model $model): ?string
    {
        $field = self::spec($type)['title'];
        $value = in_array($field, $model->translatable ?? [], true) ? $model->getTranslation($field, 'en', false) : $model->getAttribute($field);
        if ($type === 'user') {
            $value = trim($model->first_name . ' ' . $model->last_name);
        }

        return is_string($value) ? Str::limit(strip_tags($value), 120) : (string) $value;
    }

    /**
     * Alanları modele yazar (kaydetmez). Bilinmeyen / salt okunur alan hata verir.
     *
     * @return list<string>  uyarılar (ör. indirilemeyen resim)
     */
    public static function fill(string $type, Model $model, array $fields): array
    {
        $spec = self::spec($type);
        $warnings = [];
        $fillable = $model->getFillable();

        foreach ($fields as $field => $value) {
            if (!in_array($field, $fillable, true) || (in_array($field, $spec['hidden'] ?? [], true) && !in_array($field, $spec['writeOnly'] ?? [], true))) {
                throw new RuntimeException("{$type} has no editable field \"{$field}\". Fields: " . implode(', ', array_keys(self::fields($type))));
            }
            if (in_array($field, $spec['readonly'] ?? [], true) && $model->exists) {
                throw new RuntimeException("\"{$field}\" is read-only.");
            }
            $cast = $model->getCasts()[$field] ?? null;

            // Çok dilli: düz metin = İngilizce; {"tr": "..."} = o diller
            if (in_array($field, $model->translatable ?? [], true)) {
                $values = is_array($value) && !array_is_list($value) ? $value : ['en' => $value];
                foreach ($values as $locale => $text) {
                    if (is_string($text) && in_array($field, $spec['html'] ?? [], true)) {
                        $text = HtmlSanitizer::clean($text);
                    }
                    blank($text) ? $model->forgetTranslation($field, $locale) : $model->setTranslation($field, $locale, is_string($text) ? trim($text) : $text);
                }
                continue;
            }

            // Resim: adres → indir, panel gibi küçült ve kaydet; boş → kaldır
            if ($method = ($spec['images'] ?? [])[$field] ?? null) {
                $uploader = app(ImageUploader::class);
                if (blank($value)) {
                    $uploader->delete($model->getAttribute($field));
                    $model->setAttribute($field, null);
                    continue;
                }
                try {
                    $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0 TripSpoiler'])->get((string) $value);
                    if (!$response->successful() || !str_starts_with((string) $response->header('Content-Type'), 'image/')) {
                        throw new RuntimeException('not an image');
                    }
                    $model->setAttribute($field, $uploader->{$method}($response->body(), $model->getAttribute($field)));
                } catch (Throwable $e) {
                    $warnings[] = "Image for {$field} could not be downloaded from {$value}.";
                }
                continue;
            }

            $model->setAttribute($field, match (true) {
                $value === null => null,
                in_array($field, $spec['html'] ?? [], true) => HtmlSanitizer::clean((string) $value),
                $cast === 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                str_ends_with($field, '_id') && !is_numeric($value) && $value !== '' => self::relation($field, (string) $value),
                $cast && is_subclass_of($cast, BackedEnum::class) => $cast::tryFrom((string) $value) ?? throw new RuntimeException("{$field} must be one of: " . implode(', ', array_map(fn ($c) => $c->value, $cast::cases()))),
                in_array($cast, ['datetime', 'date'], true) => Carbon::parse((string) $value),
                default => $value === '' ? null : $value,
            });
        }

        // Yeni kayıt: adres (slug) başlıktan, sıra en sona
        if (!$model->exists) {
            if (in_array('slug', $fillable, true) && blank($model->getAttribute('slug'))) {
                $model->setAttribute('slug', Panel::slug($model::class, (string) self::title($type, $model)));
            }
            if (in_array('sort_order', $fillable, true) && $model->getAttribute('sort_order') === null) {
                $model->setAttribute('sort_order', (int) $model->newQuery()->max('sort_order') + 1);
            }
        }

        return $warnings;
    }

    /** "city_id": "Istanbul" gibi adla verilen ilişki → id */
    private static function relation(string $field, string $value): int
    {
        return match ($field) {
            'city_id' => Panel::city($value)->id,
            'partner_id' => Panel::partner($value)->id,
            'activity_id' => Panel::activity($value)->id,
            'blog_id' => Panel::blog($value)->id,
            'country_id' => Models\Country::where('name', $value)->orWhere('code', strtoupper($value))->value('id') ?? throw new RuntimeException("Country \"{$value}\" not found."),
            'topic_id' => Models\CityGuideTopic::where('key', $value)->orWhere('name->en', $value)->value('id') ?? throw new RuntimeException("Guide topic \"{$value}\" not found."),
            default => throw new RuntimeException("{$field} needs a numeric id."),
        };
    }
}
