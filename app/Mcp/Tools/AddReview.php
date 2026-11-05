<?php

namespace App\Mcp\Tools;

use App\Mcp\Support\Panel;
use App\Models\Review;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Add a visitor review. Without "activity" it is a general review about TripSpoiler (home page / reviews page). Approved reviews are visible on the site.')]
class AddReview extends PanelTool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Reviewer full name, e.g. "Emily Carter"')->required(),
            'rating' => $schema->integer()->description('1–5 stars')->required(),
            'comment' => $schema->string()->description('Review text (any language; usually English)')->required(),
            'activity' => $schema->string()->description('Activity id, slug or name; empty = general review'),
            'approved' => $schema->boolean()->description('Visible on the site (default true)'),
        ];
    }

    protected function run(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:2000'],
            'activity' => ['nullable'],
            'approved' => ['sometimes', 'boolean'],
        ]);

        $activity = filled($data['activity'] ?? null) ? Panel::activity($data['activity']) : null;
        $review = Review::create([
            'activity_id' => $activity?->id,
            'name' => trim($data['name']),
            'rating' => $data['rating'],
            'comment' => trim($data['comment']),
            'approved' => $data['approved'] ?? true,
        ]);

        return ['result' => 'created', 'id' => $review->id, 'where' => $activity ? $activity->getTranslation('name', 'en') : 'General (site)', 'approved' => $review->approved, 'edit_url' => route('reviews.edit', $review)];
    }
}
