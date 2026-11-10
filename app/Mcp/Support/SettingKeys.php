<?php

namespace App\Mcp\Support;

/**
 * Claude'un okuyup değiştirebildiği site ayarları (settings tablosu) ve anlamları.
 */
class SettingKeys
{
    public const KNOWN = [
        'hero_image' => 'Home hero desktop photo. To change: give an image URL (min 1600×800). null = default image.',
        'hero_image_mobile' => 'Home hero phone photo (portrait). Give an image URL, or null to crop the desktop photo.',
        'hero_focus' => 'Home hero horizontal crop on desktop, 0 (left) – 100 (right).',
        'hero_focus_mobile' => 'Home hero horizontal crop on phones, 0–100.',
        'hero_overlay' => 'Home hero darkening: soft | medium | strong.',
        'hero_featured' => 'Home hero "popular now" card: comma-separated activity ids, max 5 (empty = popular activities).',
        'hero_pass_id' => 'Home hero left white card: activity id of a city pass (empty = first pass).',
        'hero_guide_id' => 'Home hero right white card: blog post id (empty = newest post).',
        'home_blog_ids' => 'Home page blog section: comma-separated blog ids (empty = newest posts).',
        'reviews_hero_cards' => 'Reviews page banner cards: empty = automatic, "0" = hidden, or comma-separated review ids (max 3).',
        'sections_disabled' => 'Home sections hidden on the site, comma-separated from: steps, blog, features, announcements, intro, social, reviews, city_entry, partners, home_popular, home_faq (these two hide only the home page section). Empty = all visible.',
        'site_settings_order' => 'Order of the cards on the panel "Bölümler" page (comma-separated card keys).',
        'mailbox_signature' => 'E-mail signature used when replying from the panel mailbox.',
    ];

    /** Sayfa/şehir bazlı anahtarlar */
    public const PATTERNS = [
        '/^banner_(activities|cities|blog|reviews|about|privacy|contact)_focus$/' => 'Page hero horizontal crop 0–100 (e.g. banner_activities_focus).',
        '/^banner_(activities|cities|blog|reviews|about|privacy|contact)_overlay$/' => 'Page hero darkening soft|medium|strong.',
        '/^blog_hero_\d+$/' => 'Blog page arches for a city (blog_hero_{cityId}): comma-separated 3 blog ids (empty = automatic).',
    ];

    public const IMAGES = ['hero_image' => 'hero', 'hero_image_mobile' => 'heroMobile'];

    public static function describe(string $key): ?string
    {
        if (isset(self::KNOWN[$key])) {
            return self::KNOWN[$key];
        }
        foreach (self::PATTERNS as $pattern => $about) {
            if (preg_match($pattern, $key)) {
                return $about;
            }
        }

        return null;
    }
}
