<?php

namespace App\Mcp\Site;

use App\Models\Attraction;
use App\Models\Blog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Search TripSpoiler travel guides: blog articles (tips, itineraries, how-to) and attraction guides (opening hours, tickets, history). Returns title, short summary and link.')]
class SearchGuides extends SiteTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required()->description('Keywords, e.g. "Hagia Sophia dress code", "3 days in Rome"'),
            'language' => $schema->string()->description('Language code for texts and links (default en)'),
        ];
    }

    protected function run(Request $request): array
    {
        $locale = $this->locale($request->get('language'));
        $like = $this->like((string) $request->get('query'));

        $places = Attraction::visible()->with('city')
            ->where(fn ($q) => $q->where('name->en', 'like', $like)->orWhere("name->{$locale}", 'like', $like))
            ->limit(10)->get()
            ->map(fn (Attraction $place) => [
                'type' => 'attraction',
                'title' => $place->getTranslation('name', $locale),
                'city' => $place->city->name,
                'summary' => $this->plain($place->getTranslation('summary', $locale), 220),
                'url' => $this->siteUrl('attraction', $place->slug, $locale),
            ]);

        $posts = Blog::where('active', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->where('title->en', 'like', $like)->orWhere("title->{$locale}", 'like', $like)->orWhere('tags->en', 'like', $like))
            ->latest('published_at')->limit(10)->get()
            ->map(fn (Blog $post) => [
                'type' => 'article',
                'title' => $post->getTranslation('title', $locale),
                'summary' => $this->plain($post->getTranslation('excerpt', $locale), 220),
                'url' => $this->siteUrl('blog', $this->slug($post, $locale), $locale),
            ]);

        return $places->concat($posts)->values()->all();
    }
}
