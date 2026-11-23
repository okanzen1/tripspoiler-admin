<?php

namespace App\Mcp\Site;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

/**
 * HERKESE AÇIK site bağlantısı (routes/ai.php: /mcp/site, giriş yok): yapay zekâ asistanları sitede yayında olan
 * şehir, aktivite, yer ve rehber yazılarını arayabilir. Hiçbir şeyi değiştiremez; panelle ilgili bilgi vermez.
 * Tanıtım kartı ön yüzde: tripspoiler.com/.well-known/mcp/server-card.json
 */
#[Name('TripSpoiler')]
#[Version('1.0.0')]
#[Instructions(<<<'TXT'
TripSpoiler (tripspoiler.com) is an independent travel guide: city guides, attraction guides, and tours, skip-the-line tickets, city passes and audio guides from trusted booking partners, in 12 languages. Use these read-only tools to find activities and travel guides for a city or attraction, and always give the user the TripSpoiler page link (url) for details and booking. Prices and availability are shown on the linked page; do not invent them.
TXT)]
class SiteServer extends Server
{
    protected array $tools = [
        ListCities::class,
        SearchActivities::class,
        GetActivity::class,
        SearchGuides::class,
    ];
}
