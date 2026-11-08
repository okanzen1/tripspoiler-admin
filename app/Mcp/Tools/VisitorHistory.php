<?php

namespace App\Mcp\Tools;

use App\Services\Analytics;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Everything one visitor did on the site, session by session: pages with times, where they came from, partner links they clicked. Give their IP (from analytics-report).')]
class VisitorHistory extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['ip' => $schema->string()->required()];
    }

    protected function run(Request $request): array
    {
        $data = Analytics::visitor((string) $request->get('ip'));
        if ($data['visits']->isEmpty() && $data['clicks']->isEmpty()) {
            throw new \RuntimeException('No visits from this IP.');
        }
        $last = $data['visits']->first() ?? $data['clicks']->first();

        return [
            'ip' => $request->get('ip'),
            'location' => trim(($last->city ? $last->city . ', ' : '') . $last->country),
            'device' => trim($last->device . ' ' . $last->browser . ' ' . $last->os),
            'page_views' => $data['visits']->count(),
            'partner_clicks' => $data['clicks']->count(),
            'sessions' => $data['sessions']->take(20)->map(fn ($items) => $items->map(fn ($i) => empty($i->is_click)
                ? ['at' => (string) $i->created_at, 'page' => $i->path, 'from' => $i->utm_source ?: $i->referrer_host]
                : ['at' => (string) $i->created_at, 'clicked_partner' => $i->partner, 'url' => $i->target_url])->all())->all(),
        ];
    }
}
