<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use App\Models\Language;
use App\Services\Translation\ModelTranslator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Translate an activity, blog post or FAQ from English into the other site languages with Google Translate (free). Without "languages" it translates only missing/outdated languages. Slugs are never translated. Takes up to a few minutes for all languages.')]
class Translate extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->enum(['activity', 'blog', 'faq'])->required(),
            'id' => $schema->string()->description('Record id (or slug/name for activity and blog)')->required(),
            'languages' => $schema->array()->items($schema->string())->description('Language codes, e.g. ["de","tr"]; empty = all missing/outdated'),
        ];
    }

    protected function run(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:activity,blog,faq'],
            'id' => ['required'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['in:' . Language::targets()->pluck('code')->implode(',')],
        ]);
        $model = match ($data['type']) {
            'activity' => Panel::activity($data['id']),
            'blog' => Panel::blog($data['id']),
            'faq' => \App\Models\Faq::findOrFail((int) $data['id']),
        };

        set_time_limit(900);
        $translator = app(ModelTranslator::class);
        $done = $translator->translate($model, ($data['languages'] ?? []) ?: null);

        return ['translated' => $done, 'status' => $translator->status($model->fresh())];
    }
}
