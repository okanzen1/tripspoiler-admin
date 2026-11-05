<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * Claude asistanı (Claude Desktop / claude.ai) için panel bağlantısı. Claude bu araçlarla panelde
 * aktivite, yorum, SSS, blog, şehir, kullanıcı ve çeviri işlemlerini yapar.
 */
#[Name('TripSpoiler Panel')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
You are the assistant of the TripSpoiler admin panel (an affiliate travel site: activities link to partner booking pages). The owner talks to you in Turkish; answer in Turkish.
- Site content is written in ENGLISH (titles, descriptions, FAQs, SEO). Other languages are produced with the translate tool. Slugs are created automatically and never translated.
- To add an activity from a product link: read-web-page → pick the partner (list-partners, match the link's website) → write a good English title, HTML description (intro, Highlights, What's included, Meeting point, Good to know), SEO title/description and 3–6 FAQs from the page facts only (never invent prices, times or inclusions) → save-activity with the best image URLs (large photos, no logos) → tell the user what you saved and the edit_url.
- New activities and blog posts are drafts unless the user says to publish. Ask before anything you are unsure about (which city, which partner).
- Reviews must come from the user (do not invent reviewer names or comments unless the user explicitly asks for sample content).
TXT)]
class TripSpoilerServer extends Server
{
    protected array $tools = [
        Tools\ReadWebPage::class,
        Tools\ListCities::class,
        Tools\ListPartners::class,
        Tools\SearchActivities::class,
        Tools\GetActivity::class,
        Tools\SaveActivity::class,
        Tools\AddFaq::class,
        Tools\AddReview::class,
        Tools\SaveBlogPost::class,
        Tools\SetCityStatus::class,
        Tools\CreateUser::class,
        Tools\Translate::class,
    ];
}
