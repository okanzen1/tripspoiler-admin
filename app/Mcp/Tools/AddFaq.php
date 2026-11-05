<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use App\Models\Faq;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add a FAQ (English, plain text). Attach it to an activity or a blog post, or leave both empty for a general FAQ (home + FAQ page).')]
class AddFaq extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'question' => $schema->string()->required(),
            'answer' => $schema->string()->required(),
            'activity' => $schema->string()->description('Activity id, slug or name'),
            'blog' => $schema->string()->description('Blog post id, slug or title'),
        ];
    }

    protected function run(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'activity' => ['nullable'],
            'blog' => ['nullable'],
        ]);
        $activity = filled($data['activity'] ?? null) ? Panel::activity($data['activity']) : null;
        $blog = !$activity && filled($data['blog'] ?? null) ? Panel::blog($data['blog']) : null;

        $faq = Faq::create([
            'activity_id' => $activity?->id,
            'blog_id' => $blog?->id,
            'question' => ['en' => trim($data['question'])],
            'answer' => ['en' => trim($data['answer'])],
            'sort_order' => (int) Faq::where('activity_id', $activity?->id)->where('blog_id', $blog?->id)->max('sort_order') + 1,
            'active' => true,
        ]);

        return ['result' => 'created', 'id' => $faq->id, 'where' => $activity?->getTranslation('name', 'en') ?? $blog?->getTranslation('title', 'en') ?? 'General FAQ', 'edit_url' => route('faqs.edit', $faq)];
    }
}
