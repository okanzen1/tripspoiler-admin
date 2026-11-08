<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Read one record with all its fields (English text of translatable fields + which languages are translated). id can also be the slug, key or English title.')]
class GetRecord extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required(),
            'id' => $schema->string()->required()->description('id, slug, key or English title'),
            'locale' => $schema->string()->description('Optional: also return the texts in this language (e.g. "tr")'),
        ];
    }

    protected function run(Request $request): array
    {
        $type = (string) $request->get('type');
        $model = ContentTypes::find($type, (string) $request->get('id'));
        $data = ContentTypes::present($type, $model, full: true);

        if (filled($locale = $request->get('locale'))) {
            foreach ($model->translatable ?? [] as $field) {
                $data["{$field}__{$locale}"] = $model->getTranslation($field, $locale, false);
            }
        }

        return $data;
    }
}
