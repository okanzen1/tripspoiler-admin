<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Lists everything you can manage in the panel (content types) with what each is and its fields. Call this first when you are unsure which type or field to use. Give a type to see only its fields.')]
class DescribeContent extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['type' => $schema->string()->description('Optional: one content type, e.g. "blog"')];
    }

    protected function run(Request $request): array
    {
        $types = filled($request->get('type')) ? [$request->get('type') => ContentTypes::spec($request->get('type'))] : ContentTypes::TYPES;

        return collect($types)->map(fn ($spec, $type) => [
            'about' => $spec['about'],
            'fields' => ContentTypes::fields($type),
        ])->all() + ['_also' => 'Settings (hero image/focus/overlay, section on/off, ordering…): get-settings / save-settings. Analytics: analytics-report, visitor-history. Who changed what: activity-log. Translations: translate.'];
    }
}
