<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use RuntimeException;

#[IsReadOnly]
#[Description('Fetch a web page (e.g. a partner product page such as Megapass, GetYourGuide, Viator) and return its title, meta description, structured data (JSON-LD: product, price, duration…), image URLs and the readable text. Use it before create_activity when the user gives a product link.')]
class ReadWebPage extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['url' => $schema->string()->description('Full https:// URL')->required()];
    }

    protected function run(Request $request): array
    {
        $url = $request->validate(['url' => ['required', 'url']])['url'];
        $response = Http::timeout(30)->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130 Safari/537.36',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])->get($url);

        if (!$response->successful()) {
            throw new RuntimeException("The page returned HTTP {$response->status()}.");
        }

        $html = $response->body();
        $meta = fn (string $name) => preg_match('#<meta[^>]+(?:property|name)=["\']' . preg_quote($name, '#') . '["\'][^>]+content=["\']([^"\']*)#i', $html, $m) ? html_entity_decode($m[1]) : null;
        preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $ld);
        preg_match_all('#<img[^>]+src=["\'](https?://[^"\']+)#i', $html, $imgs);

        $text = preg_replace('#<(script|style|noscript|svg|nav|footer|header)\b.*?</\1>#is', ' ', $html);
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#<(br|/p|/div|/li|/h\d)[^>]*>#i', "\n", $text)))));

        return [
            'url' => $url,
            'title' => preg_match('#<title[^>]*>(.*?)</title>#is', $html, $t) ? trim(html_entity_decode($t[1])) : null,
            'description' => $meta('description') ?? $meta('og:description'),
            'og_image' => $meta('og:image'),
            'structured_data' => collect($ld[1])->map(fn ($json) => json_decode(trim($json), true))->filter()->values()->all(),
            'images' => collect($imgs[1])->unique()->reject(fn ($src) => preg_match('#\.(svg|gif)(\?|$)|logo|icon|sprite|pixel#i', $src))->take(30)->values()->all(),
            'text' => Str::limit($text, 15000),
        ];
    }
}
