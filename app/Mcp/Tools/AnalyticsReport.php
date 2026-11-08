<?php

namespace App\Mcp\Tools;

use App\Services\Analytics;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Site analytics (bots excluded): who is on the site right now, today\'s visitors, and for the last 7/30/90 days visitors, page views, partner clicks, click rate (with change vs the previous period), top pages, traffic sources, devices, countries, partners, plus the latest visitors grouped by IP.')]
class AnalyticsReport extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->description('7, 30 or 90 (default 30)'),
            'visitors' => $schema->integer()->description('How many recent visitors (grouped by IP) to include, default 15, max 50'),
            'country' => $schema->string()->description('Optional: only visitors from this country code, e.g. "TR"'),
        ];
    }

    protected function run(Request $request): array
    {
        $days = in_array((int) $request->get('days'), [7, 30, 90], true) ? (int) $request->get('days') : 30;
        $analytics = new Analytics($days);
        $live = Analytics::live(['range' => min($days, 90), 'country' => $request->get('country')], min(50, max(1, (int) ($request->get('visitors') ?: 15))));

        return [
            'period' => $analytics->start->toDateString() . ' – ' . $analytics->end->toDateString(),
            'now_on_site' => $live['active'],
            'unique_visitors_today' => $live['today'],
            'last_visit_at' => $live['last_at']?->toDateTimeString(),
            'pages_open_now' => $live['pages']->map(fn ($p) => ['path' => $p->path, 'people' => (int) $p->total])->all(),
            'totals' => $analytics->kpis(),
            'top_pages' => $analytics->topPages(),
            'sources' => $analytics->sources(),
            'devices' => $analytics->devices(),
            'countries' => $analytics->countries(),
            'partner_clicks' => $analytics->partners(),
            'recent_visitors' => $live['visitors']->map(fn ($v) => [
                'ip' => $v->last->ip, 'country' => $v->last->country, 'city' => $v->last->city,
                'last_seen' => (string) $v->last->created_at, 'last_page' => $v->last->path,
                'pages' => $v->views, 'sessions' => $v->sessions, 'partner_clicks' => $v->clicks,
                'came_from' => $v->first->utm_source ?: ($v->first->referrer_host ?: 'direct'),
                'device' => trim($v->last->device . ' ' . $v->last->browser . ' ' . $v->last->os),
            ])->all(),
        ];
    }
}
