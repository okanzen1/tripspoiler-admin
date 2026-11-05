<?php

namespace App\Mcp\Tools;

use App\Enums\ActivityType;
use App\Mcp\Support\Panel;
use App\Models\Activity;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TXT'
Create a new activity, or update one when "activity" is given (only the fields you send change).
Write all texts in English (other languages come from the translate tool). description_html is clean HTML: <h2>/<h3> section titles, <p>, <ul><li>, <strong>; no inline styles, no images inside.
Typical description sections: short intro, Highlights, What's included / not included, Meeting point, Good to know.
image_urls are downloaded and resized (first one becomes the cover; up to 15). faqs are added to the activity.
New activities are saved as drafts (active=false) unless the user asks to publish. Always tell the user the edit_url.
TXT)]
class SaveActivity extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'activity' => $schema->string()->description('To UPDATE: activity id or slug. Leave empty to CREATE.'),
            'name' => $schema->string()->description('English title, e.g. "Hagia Sophia Skip-the-Line Ticket with Audio Guide" (required when creating)'),
            'city' => $schema->string()->description('City name or id, e.g. "Istanbul" or "Izmir, Turkey" (required when creating). The city is activated automatically.'),
            'partner' => $schema->string()->description('Partner name, e.g. "Megapass" (see list-partners)'),
            'type' => $schema->string()->enum(['product', 'pass', 'package'])->description('product = single tour/ticket, pass = city card, package = bundle'),
            'affiliate_url' => $schema->string()->description('The partner product link visitors are sent to'),
            'partner_product_id' => $schema->string()->description('Product id/code in the partner system, if visible'),
            'duration' => $schema->string()->description('e.g. "2 hours", "1 day", "Flexible"'),
            'audio_guide' => $schema->boolean(),
            'description_html' => $schema->string()->description('English description as HTML'),
            'meta_title' => $schema->string()->description('SEO title, max ~60 chars, e.g. "Hagia Sophia Tickets & Audio Guide | TripSpoiler"'),
            'meta_description' => $schema->string()->description('SEO description, 120–160 chars'),
            'popular' => $schema->boolean()->description('Show in "Most popular" on home/city pages'),
            'active' => $schema->boolean()->description('Published on the site'),
            'image_urls' => $schema->array()->items($schema->string())->description('Image URLs to download and add (largest/best quality first)'),
            'faqs' => $schema->array()->items($schema->object([
                'question' => $schema->string()->required(),
                'answer' => $schema->string()->required(),
            ]))->description('Questions and answers to add (English, plain text)'),
        ];
    }

    protected function run(Request $request): array
    {
        $activity = filled($request->get('activity')) ? Panel::activity($request->get('activity')) : new Activity(['type' => ActivityType::Product, 'active' => false]);
        $creating = !$activity->exists;

        $data = $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'city' => [$creating ? 'required' : 'sometimes'],
            'partner' => ['nullable'],
            'type' => ['sometimes', Rule::enum(ActivityType::class)],
            'affiliate_url' => ['nullable', 'url', 'max:2000'],
            'partner_product_id' => ['nullable', 'string', 'max:100'],
            'duration' => ['nullable', 'string', 'max:50'],
            'audio_guide' => ['sometimes', 'boolean'],
            'description_html' => ['nullable', 'string', 'max:100000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'popular' => ['sometimes', 'boolean'],
            'active' => ['sometimes', 'boolean'],
            'image_urls' => ['nullable', 'array', 'max:15'],
            'image_urls.*' => ['url'],
            'faqs' => ['nullable', 'array', 'max:30'],
            'faqs.*.question' => ['required', 'string', 'max:500'],
            'faqs.*.answer' => ['required', 'string', 'max:5000'],
        ]);

        $images = ['saved' => [], 'failed' => []];
        DB::transaction(function () use ($activity, $creating, $data, &$images) {
            if (array_key_exists('city', $data)) {
                $city = Panel::city($data['city']);
                $city->update(['active' => true]);
                $city->country->update(['active' => true]);
                $activity->city_id = $city->id;
            }
            if (array_key_exists('partner', $data)) {
                $activity->partner_id = Panel::partner($data['partner'])?->id;
            }
            if (isset($data['name'])) {
                $activity->setTranslation('name', 'en', trim($data['name']));
            }
            foreach (['description_html' => 'description', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description'] as $input => $field) {
                if (array_key_exists($input, $data)) {
                    $value = $field === 'description' ? HtmlSanitizer::clean($data[$input]) : $data[$input];
                    filled($value) ? $activity->setTranslation($field, 'en', $value) : $activity->forgetTranslation($field, 'en');
                }
            }
            foreach (['type' => 'type', 'affiliate_url' => 'affiliate_url', 'partner_product_id' => 'partner_product_id', 'duration' => 'duration', 'audio_guide' => 'audio_guide', 'popular' => 'is_popular', 'active' => 'active'] as $input => $field) {
                if (array_key_exists($input, $data)) {
                    $activity->{$field} = $data[$input];
                }
            }
            if ($creating) {
                $activity->slug = Panel::slug(Activity::class, $data['name']);
                $activity->sort_order = (int) Activity::where('city_id', $activity->city_id)->max('sort_order') + 1;
            }
            $activity->save();

            $order = (int) $activity->images()->max('sort_order');
            $images = Panel::downloadImages($data['image_urls'] ?? [], fn ($binary) => $activity->images()->create([
                ...app(ImageUploader::class)->activity($binary), 'sort_order' => $creating && !$activity->images()->exists() ? 0 : ++$order,
            ]));

            $faqOrder = (int) $activity->faqs()->max('sort_order');
            foreach ($data['faqs'] ?? [] as $faq) {
                $activity->faqs()->create(['question' => ['en' => trim($faq['question'])], 'answer' => ['en' => trim($faq['answer'])], 'sort_order' => ++$faqOrder, 'active' => true]);
            }
        });

        $activity->refresh()->load('city');

        return [
            'result' => $creating ? 'created' : 'updated',
            'id' => $activity->id,
            'name' => $activity->getTranslation('name', 'en'),
            'slug' => $activity->slug,
            'city' => $activity->city->name,
            'active' => $activity->active,
            'images_added' => count($images['saved']),
            'images_failed' => $images['failed'],
            'faqs_added' => count($data['faqs'] ?? []),
            'edit_url' => route('activities.edit', $activity),
            'note' => 'Texts are English only. Use the translate tool to translate into the other site languages.',
        ];
    }
}
