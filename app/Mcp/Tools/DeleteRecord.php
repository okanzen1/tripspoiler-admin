<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\ContentTypes;
use App\Services\ImageUploader;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
#[Description('Permanently delete a record. ONLY after the user explicitly confirmed this exact deletion in the chat: first tell them what will be deleted (type, title, id) and wait for their "yes". Set user_confirmed=true only then.')]
class DeleteRecord extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->required(),
            'id' => $schema->string()->required(),
            'user_confirmed' => $schema->boolean()->required()->description('true only if the user said yes to deleting this record'),
        ];
    }

    protected function run(Request $request): array
    {
        $type = (string) $request->get('type');
        $model = ContentTypes::find($type, (string) $request->get('id'));
        $title = ContentTypes::title($type, $model);

        if ($request->get('user_confirmed') !== true) {
            return ['result' => 'not deleted', 'ask_user' => "Ask the user to confirm deleting {$type} #{$model->getKey()} \"{$title}\"."];
        }
        if ($type === 'user' && $model->is(auth()->user())) {
            throw new \RuntimeException('You cannot delete the user you are connected as.');
        }

        // Kayda bağlı resim dosyaları da silinsin
        foreach (array_keys(ContentTypes::spec($type)['images'] ?? []) as $field) {
            app(ImageUploader::class)->delete($model->getAttribute($field));
        }
        $model->delete();

        return ['result' => 'deleted', 'type' => $type, 'id' => $model->getKey(), 'title' => $title];
    }
}
