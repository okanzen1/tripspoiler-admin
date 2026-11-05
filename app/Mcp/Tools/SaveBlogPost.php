<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use App\Models\Blog;
use App\Services\ImageUploader;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description(<<<'TXT'
Create a blog post, or update one when "blog" is given (only sent fields change). English only; content_html uses <h2>/<h3>, <p>, <ul><li>, <strong>, <a href>.
cover_image_url is downloaded as the cover. related_activities are shown under the post ("related tours", where clicks come from).
New posts are drafts (active=false) unless the user asks to publish. Always tell the user the edit_url.
TXT)]
class SaveBlogPost extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'blog' => $schema->string()->description('To UPDATE: blog id or slug. Empty = CREATE'),
            'title' => $schema->string()->description('Required when creating'),
            'excerpt' => $schema->string()->description('1–2 sentence summary shown in the blog list'),
            'content_html' => $schema->string(),
            'city' => $schema->string()->description('City name or id (optional)'),
            'tags' => $schema->array()->items($schema->string()),
            'cover_image_url' => $schema->string(),
            'related_activities' => $schema->array()->items($schema->string())->description('Activity ids, slugs or names'),
            'meta_title' => $schema->string(),
            'meta_description' => $schema->string(),
            'active' => $schema->boolean(),
        ];
    }

    protected function run(Request $request): array
    {
        $blog = filled($request->get('blog')) ? Panel::blog($request->get('blog')) : new Blog(['active' => false]);
        $creating = !$blog->exists;
        $data = $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content_html' => ['nullable', 'string', 'max:200000'],
            'city' => ['nullable'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['string', 'max:50'],
            'cover_image_url' => ['nullable', 'url'],
            'related_activities' => ['nullable', 'array', 'max:12'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['title'])) {
            $blog->setTranslation('title', 'en', trim($data['title']));
        }
        foreach (['excerpt' => 'excerpt', 'content_html' => 'content', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'tags' => 'tags'] as $input => $field) {
            if (array_key_exists($input, $data)) {
                $value = $field === 'content' ? HtmlSanitizer::clean($data[$input]) : $data[$input];
                filled($value) ? $blog->setTranslation($field, 'en', $value) : $blog->forgetTranslation($field, 'en');
            }
        }
        if (array_key_exists('city', $data)) {
            $blog->city_id = filled($data['city']) ? Panel::city($data['city'])->id : null;
        }
        if (array_key_exists('active', $data)) {
            $blog->active = $data['active'];
            $blog->published_at ??= $data['active'] ? now() : null;
        }
        if ($creating) {
            $blog->slug = Panel::slug(Blog::class, $data['title']);
        }

        $coverFailed = false;
        if (filled($data['cover_image_url'] ?? null)) {
            $cover = Panel::downloadImages([$data['cover_image_url']], fn ($binary) => app(ImageUploader::class)->cover($binary, replace: $blog->cover_path));
            $blog->cover_path = $cover['saved'][0] ?? $blog->cover_path;
            $coverFailed = $cover['failed'] !== [];
        }
        $blog->save();

        if (array_key_exists('related_activities', $data)) {
            $ids = collect($data['related_activities'] ?? [])->map(fn ($a) => Panel::activity($a)->id)->unique()->values();
            $blog->activities()->sync($ids->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
        }

        return [
            'result' => $creating ? 'created' : 'updated', 'id' => $blog->id, 'slug' => $blog->slug, 'active' => $blog->active,
            'cover' => (bool) $blog->cover_path, 'cover_failed' => $coverFailed, 'edit_url' => route('blogs.edit', $blog),
        ];
    }
}
