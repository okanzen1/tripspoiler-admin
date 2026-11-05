<?php

namespace App\Mcp\Tools;

use App\Models\Partner;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List affiliate partners (Megapass, GetYourGuide…). An activity links to one partner; match the partner by the product link\'s website.')]
class ListPartners extends PanelTool
{
    protected function run(Request $request): array
    {
        return Partner::withCount('activities')->orderBy('name')->get()
            ->map(fn (Partner $p) => ['id' => $p->id, 'name' => $p->name, 'website' => $p->website, 'active' => $p->active, 'activities' => $p->activities_count])
            ->all();
    }
}
