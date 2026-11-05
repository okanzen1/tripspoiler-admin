<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Show (activate) or hide (deactivate) a city on the site. Activating a city also activates its country. Use list-cities first if the name is ambiguous.')]
class SetCityStatus extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'city' => $schema->string()->description('City name or id, e.g. "Izmir" or "Paris, FR"')->required(),
            'active' => $schema->boolean()->description('true = visible on the site, false = hidden')->required(),
        ];
    }

    protected function run(Request $request): array
    {
        $data = $request->validate(['city' => ['required'], 'active' => ['required', 'boolean']]);
        $city = Panel::city($data['city']);

        $city->update(['active' => $data['active']]);
        if ($data['active']) {
            $city->country->update(['active' => true]);
        }

        return ['city' => $city->name, 'country' => $city->country->name, 'active' => $city->active, 'country_active' => $city->country->fresh()->active, 'slug' => $city->slug];
    }
}
