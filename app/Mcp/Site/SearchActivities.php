<?php

namespace App\Mcp\Site;

use App\Models\Activity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Search TripSpoiler tours, skip-the-line tickets, city passes and audio guides by keyword and/or city. Returns name, city, duration, a short summary and the page link (where it can be booked).')]
class SearchActivities extends SiteTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Keywords, e.g. "Colosseum", "Bosphorus cruise", "museum pass"'),
            'city' => $schema->string()->description('City name, e.g. "Rome"'),
            'language' => $schema->string()->description('Language code for texts and links (default en)'),
        ];
    }

    protected function run(Request $request): array
    {
        $locale = $this->locale($request->get('language'));
        $query = trim((string) $request->get('query'));
        $city = trim((string) $request->get('city'));

        return Activity::visible()->with('city')
            ->when($query !== '', fn ($q) => $q->where(fn ($q) => $q->where('name->en', 'like', $this->like($query))
                ->orWhere("name->{$locale}", 'like', $this->like($query))->orWhere('slug', 'like', $this->like(str_replace(' ', '-', $query)))))
            ->when($city !== '', fn ($q) => $q->whereHas('city', fn ($c) => $c->where('name', 'like', $this->like($city))->orWhere('slug', 'like', $this->like($city))))
            ->orderByDesc('is_popular')->orderBy('sort_order')->limit(20)->get()
            ->map(fn (Activity $a) => [
                'slug' => $a->slug,
                'name' => $a->getTranslation('name', $locale),
                'city' => $a->city->name,
                'duration' => $a->duration,
                'summary' => $this->plain($a->getTranslation('meta_description', $locale), 220) ?? $this->plain($a->getTranslation('description', $locale), 220),
                'url' => $this->siteUrl('activity', $this->slug($a, $locale), $locale),
            ])->all();
    }
}
