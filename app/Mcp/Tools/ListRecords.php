<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('List or search records of a content type (activity, blog, faq, review, announcement, step, feature, section_text, page, city, partner, redirect, user… see describe-content). Returns id, title, status and the panel edit_url.')]
class ListRecords extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required()->description('Content type, e.g. "faq"'),
            'search' => $schema->string()->description('Words to look for in the title/name'),
            'where' => $schema->object()->description('Exact filters, e.g. {"active": true, "city_id": 3, "page_key": "home"}'),
            'limit' => $schema->integer()->description('Default 30, max 200'),
            'offset' => $schema->integer(),
        ];
    }

    protected function run(Request $request): array
    {
        $type = (string) $request->get('type');
        $spec = ContentTypes::spec($type);
        $model = ContentTypes::model($type);
        $query = $model->newQuery();

        if (filled($search = trim((string) $request->get('search')))) {
            $query->where(function ($q) use ($spec, $model, $search) {
                foreach ($spec['search'] as $field) {
                    $column = in_array($field, $model->translatable ?? [], true) ? "{$field}->en" : $field;
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
        foreach ((array) $request->get('where', []) as $field => $value) {
            if (!in_array($field, $model->getFillable(), true) || in_array($field, $spec['hidden'] ?? [], true)) {
                throw new \RuntimeException("Cannot filter by \"{$field}\".");
            }
            is_bool($value) ? $query->where($field, $value) : ($value === null ? $query->whereNull($field) : $query->where($field, $value));
        }

        $total = (clone $query)->count();
        $limit = min(200, max(1, (int) ($request->get('limit') ?: 30)));
        $orderBy = in_array('sort_order', $model->getFillable(), true) ? 'sort_order' : $model->getKeyName();
        $rows = $query->orderBy($orderBy)->orderBy($model->getKeyName())->skip((int) $request->get('offset', 0))->take($limit)->get();

        return ['total' => $total, 'records' => $rows->map(fn ($row) => ContentTypes::present($type, $row))->all()];
    }
}
