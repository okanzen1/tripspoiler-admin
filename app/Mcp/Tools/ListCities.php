<?php

namespace App\Mcp\Tools;

use App\Models\City;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Search cities (all world cities are in the database, most inactive). Returns id, name, country and whether it is visible on the site. Without a search it lists the active (visible) cities.')]
class ListCities extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['search' => $schema->string()->description('City name, e.g. "Izmir" or "Rome"')];
    }

    protected function run(Request $request): array
    {
        $search = trim((string) $request->get('search'));

        return City::with('country')->withCount('activities')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"), fn ($q) => $q->where('active', true))
            ->orderByDesc('active')->orderByDesc('population')->limit(25)->get()
            ->map(fn (City $c) => [
                'id' => $c->id, 'name' => $c->name, 'country' => $c->country->name, 'slug' => $c->slug,
                'active' => $c->active, 'country_active' => $c->country->active, 'activities' => $c->activities_count,
            ])->all();
    }
}
