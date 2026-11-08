<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Create a record (no id) or update one (with id; only the given fields change). Use for anything in the panel: FAQs, reviews, announcements, steps, features, section texts, home hero texts, page SEO, page banners, cities, countries, guide sections, partners, redirects, languages, users… Translatable fields: plain string = English; {"tr": "...", "de": "..."} sets those languages directly. Image fields: give an image URL. Only do what the user asked.')]
class SaveRecord extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->description('Content type (describe-content lists them)'),
            'id' => $schema->string()->description('Record to update (id, slug, key or English title). Empty = create new.'),
            'fields' => $schema->object()->required()->description('Field values, e.g. {"question": "...", "answer": "...", "activity_id": 12}'),
        ];
    }

    protected function run(Request $request): array
    {
        $type = (string) $request->get('type');
        $fields = (array) $request->get('fields', []);
        if (!$fields) {
            throw new \RuntimeException('Give at least one field.');
        }

        $model = filled($request->get('id')) ? ContentTypes::find($type, (string) $request->get('id')) : ContentTypes::model($type);
        $created = !$model->exists;
        $warnings = ContentTypes::fill($type, $model, $fields);
        $model->save();

        return array_filter([
            'result' => $created ? 'created' : 'updated',
            'record' => ContentTypes::present($type, $model->fresh(), full: true),
            'warnings' => $warnings ?: null,
            'note' => array_intersect(array_keys($fields), $model->translatable ?? []) ? 'English changed: run translate to update the other languages if the user wants.' : null,
        ]);
    }
}
