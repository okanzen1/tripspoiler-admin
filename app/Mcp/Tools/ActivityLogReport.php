<?php

namespace App\Mcp\Tools;

use App\Models\ActivityLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Panel change history (Yönetim › İşlem kaydı): who created/updated/deleted/translated what and when, with old → new values; logins and failed login attempts.')]
class ActivityLogReport extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Default 30, max 200'),
            'action' => $schema->string()->description('created | updated | deleted | translated | login | logout | login_failed'),
            'type' => $schema->string()->description('e.g. activity, blog, setting, hero_text'),
            'user' => $schema->string()->description('Name or e-mail of the panel user'),
            'since' => $schema->string()->description('Date, e.g. 2026-11-01'),
        ];
    }

    protected function run(Request $request): array
    {
        return ActivityLog::query()
            ->when($request->get('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->get('type'), fn ($q, $v) => $q->where('subject_type', $v))
            ->when($request->get('user'), fn ($q, $v) => $q->where('user_name', 'like', "%{$v}%"))
            ->when($request->get('since'), fn ($q, $v) => $q->where('created_at', '>=', \Carbon\Carbon::parse($v)->startOfDay()))
            ->latest('id')->limit(min(200, max(1, (int) ($request->get('limit') ?: 30))))->get()
            ->map(fn ($log) => array_filter([
                'at' => $log->created_at->toDateTimeString(), 'who' => $log->user_name, 'action' => $log->action,
                'type' => $log->subject_type, 'id' => $log->subject_id, 'title' => $log->subject_label,
                'changes' => $log->changes, 'ip' => $log->ip,
            ], fn ($v) => $v !== null && $v !== []))->all();
    }
}
