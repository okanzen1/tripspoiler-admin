<?php

namespace App\Mcp\Tools;

use App\Models\Activity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Search activities by name or slug (empty search = all). Returns id, name, slug, city, partner, published and popular flags, image count and the panel edit link.')]
class SearchActivities extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return ['search' => $schema->string()->description('Part of the name or slug')];
    }

    protected function run(Request $request): array
    {
        $search = trim((string) $request->get('search'));

        return Activity::with(['city', 'partner'])->withCount(['images', 'faqs', 'reviews'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name->en', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")))
            ->orderBy('city_id')->orderBy('sort_order')->limit(50)->get()
            ->map(fn (Activity $a) => [
                'id' => $a->id, 'name' => $a->getTranslation('name', 'en'), 'slug' => $a->slug, 'type' => $a->type->value,
                'city' => $a->city->name, 'partner' => $a->partner?->name, 'active' => $a->active, 'popular' => $a->is_popular,
                'images' => $a->images_count, 'faqs' => $a->faqs_count, 'reviews' => $a->reviews_count,
                'edit_url' => route('activities.edit', $a),
            ])->all();
    }
}
