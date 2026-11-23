<?php

namespace App\Mcp\Site;

use App\Models\City;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List the cities TripSpoiler covers, with country, number of tours/tickets and the city travel guide link.')]
class ListCities extends SiteTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['language' => $schema->string()->description('Language code for links, e.g. "en", "de", "tr" (default en)')];
    }

    protected function run(Request $request): array
    {
        $locale = $this->locale($request->get('language'));

        return City::visible()->with('country')
            ->withCount(['activities' => fn ($q) => $q->where('active', true)])
            ->whereHas('activities', fn ($q) => $q->where('active', true))
            ->orderByDesc('activities_count')->limit(100)->get()
            ->map(fn (City $city) => [
                'city' => $city->name,
                'country' => $city->country?->name,
                'tours_and_tickets' => $city->activities_count,
                'guide_url' => $this->siteUrl('city', $city->slug, $locale),
            ])->all();
    }
}
