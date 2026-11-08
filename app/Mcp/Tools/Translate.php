<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use App\Models\Language;
use App\Services\Translation\ModelTranslator;
use App\Support\ActivityLogger;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Translate a record from English into the other site languages with Google Translate (free): activity, blog, faq, announcement, step, feature, section_text, hero_text, page (SEO), city_guide, city_guide_section, city_guide_topic. Default: only missing/outdated languages; all=true re-translates every language (overwrites manual fixes). Only when the user asks. Can take a minute.')]
class Translate extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->description('Content type, e.g. "section_text"'),
            'id' => $schema->string()->required()->description('id, slug, key or English title'),
            'languages' => $schema->array()->items($schema->string())->description('Language codes, e.g. ["de","tr"]; empty = missing/outdated ones'),
            'all' => $schema->boolean()->description('true = re-translate every language even if up to date'),
        ];
    }

    protected function run(Request $request): array
    {
        $targets = Language::targets()->pluck('code')->all();
        $data = $request->validate([
            'type' => ['required', 'string'],
            'id' => ['required'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['in:' . implode(',', $targets)],
            'all' => ['nullable', 'boolean'],
        ]);
        $model = ContentTypes::find($data['type'], (string) $data['id']);
        if (!($model->translatable ?? [])) {
            throw new \RuntimeException("{$data['type']} has no translatable text.");
        }

        $locales = ($data['languages'] ?? []) ?: (!empty($data['all']) ? $targets : null);
        set_time_limit(900);
        $translator = app(ModelTranslator::class);
        $done = $translator->translate($model, $locales);
        if ($done) {
            ActivityLogger::write('translated', \Illuminate\Support\Str::snake(class_basename($model)), $model->getKey(), ContentTypes::title($data['type'], $model),
                ['Diller' => [null, Language::whereIn('code', $done)->pluck('native_name')->implode(', ')]]);
        }

        return ['translated' => $done ?: 'already up to date', 'status' => $translator->status($model->fresh())];
    }
}
