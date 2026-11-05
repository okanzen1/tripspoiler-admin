<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Get one activity with all English fields, FAQs, review count, images and which languages are translated.')]
class GetActivity extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['activity' => $schema->string()->description('Activity id, slug or exact English name')->required()];
    }

    protected function run(Request $request): array
    {
        $a = Panel::activity($request->get('activity'))->load(['city.country', 'partner', 'images', 'faqs']);

        return [
            'id' => $a->id,
            'name' => $a->getTranslation('name', 'en'),
            'slug' => $a->slug,
            'type' => $a->type->value,
            'city' => $a->city->name . ', ' . $a->city->country->name,
            'partner' => $a->partner?->name,
            'affiliate_url' => $a->affiliate_url,
            'partner_product_id' => $a->partner_product_id,
            'duration' => $a->duration,
            'audio_guide' => $a->audio_guide,
            'description_html' => $a->getTranslation('description', 'en', false),
            'meta_title' => $a->getTranslation('meta_title', 'en', false),
            'meta_description' => $a->getTranslation('meta_description', 'en', false),
            'active' => $a->active,
            'popular' => $a->is_popular,
            'images' => $a->images->map(fn ($i) => $i->url())->all(),
            'faqs' => $a->faqs->map(fn ($f) => ['id' => $f->id, 'question' => $f->getTranslation('question', 'en'), 'answer' => $f->getTranslation('answer', 'en')])->all(),
            'reviews' => $a->reviews()->count(),
            'translated_languages' => $a->otherLocales(),
            'edit_url' => route('activities.edit', $a),
        ];
    }
}
