<?php

namespace App\Mcp\Site;

use App\Models\Activity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Details of one TripSpoiler tour / ticket (by slug from search_activities): description, highlights, what is included / excluded, meeting point, good to know, cancellation, FAQs and the page link.')]
class GetActivity extends SiteTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'slug' => $schema->string()->required()->description('Activity slug'),
            'language' => $schema->string()->description('Language code (default en)'),
        ];
    }

    protected function run(Request $request): array
    {
        $locale = $this->locale($request->get('language'));
        $activity = Activity::visible()->with(['city', 'faqs' => fn ($q) => $q->where('active', true)])
            ->where('slug', (string) $request->get('slug'))->firstOrFail();
        $field = fn (string $name, int $limit = 1200) => in_array($name, $activity->getTranslatableAttributes(), true)
            ? $this->plain($activity->getTranslation($name, $locale), $limit) : null;

        return array_filter([
            'name' => $activity->getTranslation('name', $locale),
            'city' => $activity->city->name,
            'duration' => $activity->duration,
            'description' => $field('description', 2000),
            'highlights' => $field('highlights'),
            'included' => $field('included'),
            'excluded' => $field('excluded'),
            'meeting_point' => $field('meeting_point'),
            'good_to_know' => $field('important_info'),
            'cancellation' => $field('cancellation'),
            'faqs' => $activity->faqs->map(fn ($faq) => ['q' => $faq->getTranslation('question', $locale), 'a' => $this->plain($faq->getTranslation('answer', $locale), 500)])->values()->all(),
            'url' => $this->siteUrl('activity', $this->slug($activity, $locale), $locale),
        ], fn ($value) => $value !== null && $value !== []);
    }
}
